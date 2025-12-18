<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types;

use App\Enums\Sr\SrType;
use App\Models\Sr;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiDetailedResponse;
use App\Services\ApiManager\Response\Handlers\ResponseHandler;
use App\Services\ApiManager\Response\ResponseManager;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\Tools\XmlService;
use Exception;

class PopulateTypeXml extends PopulateTypeBase
{
    public function __construct(
        protected ApiRequestService $requestOperation,
        protected SrConfigService   $srConfigService,
        protected ResponseHandler   $responseHandler,
        protected XmlService        $xmlService
    ) {
        parent::__construct($requestOperation, $srConfigService, $responseHandler);
        $this->setReservedKeys(
            array_combine(
                array_column(
                    DataConstants::XML_SERVICE_RESPONSE_KEYS,
                    DataConstants::RESPONSE_KEY_NAME
                ),
                array_map(function ($item) {
                    return null;
                }, DataConstants::XML_SERVICE_RESPONSE_KEYS)
            )
        );
    }


    public function runSrRequest(Sr $sr, ?array $query = []): ApiDetailedResponse
    {
        $provider = $sr->provider()->first();
        $this->requestOperation->setProviderName($provider->name);
        $this->requestOperation->setApiRequestName($sr->name);
        $this->requestOperation->setUser($this->getUser());
        return $this->requestOperation->getOperationRequestContent('raw', $query, true);
    }

    public function handleResponse(Sr $sr, ApiDetailedResponse $response): bool
    {
        $this->score = [];
        $this->response = $response;
        return match ($response->getStatus()) {
            'success' => match (ResponseManager::getSrResponseContentType($sr, $response->getResponse())) {
                ResponseManager::CONTENT_TYPE_XML => $this->handleXmlResponse($sr),
                default => false,
            },
            default => false,
        };
    }

    private function handleXmlResponse(Sr $sr)
    {
        $responseContent = $this->response->getResponse()->body();
        if (empty($responseContent)) {
            return false;
        }

        if (empty($this->data['items_array_key'])) {
            throw new Exception('items_array_key is missing from the request data.');
        }
        if (empty($this->data['item_repeater_key'])) {
            throw new Exception('item_repeater_key is missing from request data.');
        }

        $this->responseHandler->setSr($this->destSr);
        $itemsArrayValue = $this->data['items_array_key'];
        $itemRepeaterKey = $this->data['item_repeater_key'];
        $filterItemsArrayValue = $this->responseHandler->filterItemsArrayValue($itemsArrayValue);
        $responseArray = $this->xmlService->parseXmlContent(
            $responseContent,
            $filterItemsArrayValue["value"],
            $filterItemsArrayValue["brackets"],
            $this->responseHandler->filterItemsArrayValue($itemRepeaterKey)["value"]
        );
        if (!count($responseArray)) {
            return false;
        }
        $buildItemList = $this->responseHandler->buildItemListFromResponseArray($itemsArrayValue, $responseArray);
        if (!count($buildItemList)) {
            return false;
        }

        $this->destSr->items_array_key = $itemsArrayValue;
        if (!$this->destSr->save()) {
            $this->addError(
                "error",
                "Error saving items_array_key."
            );
            return false;
        }

        $this->destSr->item_repeater_key = $itemRepeaterKey;
        if (!$this->destSr->save()) {
            $this->addError(
                "error",
                "Error saving item_repeater_key."
            );
            return false;
        }

        return $this->srTypeHandler(
            $sr,
            $buildItemList,
            $itemsArrayValue
        );
    }

    private function srTypeHandler(Sr $sr, array $data, string $itemArrayType): bool
    {
        return match ($sr->type) {
            SrType::LIST => $this->populateResponseKeys($data[array_key_first($data)]),
            SrType::SINGLE, SrType::DETAIL => $this->populateResponseKeys($data),
            default => false,
        };
    }
}
