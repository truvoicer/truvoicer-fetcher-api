<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types;

use Truvoicer\TruFetcherGet\Enums\Api\ApiListKey;
use Truvoicer\TruFetcherGet\Enums\Sr\SrType;
use Truvoicer\TruFetcherGet\Models\Sr;
use Truvoicer\TruFetcherGet\Services\ApiManager\Data\DataConstants;
use Truvoicer\TruFetcherGet\Services\ApiManager\Data\DataProcessor;
use Truvoicer\TruFetcherGet\Services\ApiManager\Operations\ApiRequestService;
use Truvoicer\TruFetcherGet\Services\ApiManager\Response\Entity\ApiDetailedResponse;
use Truvoicer\TruFetcherGet\Services\ApiManager\Response\Handlers\ResponseHandler;
use Truvoicer\TruFetcherGet\Services\ApiManager\Response\ResponseManager;
use Truvoicer\TruFetcherGet\Services\ApiServices\ServiceRequests\SrConfigService;
use Truvoicer\TruFetcherGet\Services\Tools\XmlService;
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

        if (empty($this->data[ApiListKey::LIST_KEY->value])) {
            throw new Exception(ApiListKey::LIST_KEY->value .' is missing from the request data.');
        }
        if (empty($this->data[ApiListKey::LIST_ITEM_REPEATER_KEY->value])) {
            throw new Exception(ApiListKey::LIST_ITEM_REPEATER_KEY->value . ' is missing from request data.');
        }
        $this->responseHandler->setSr($this->destSr);
        $itemsArrayValue = $this->data[ApiListKey::LIST_KEY->value];
        $itemRepeaterKey = $this->data[ApiListKey::LIST_ITEM_REPEATER_KEY->value];
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

        $this->destSr->{ApiListKey::LIST_KEY->value} = $itemsArrayValue;
        if (!$this->destSr->save()) {
            $this->addError(
                "error",
                "Error saving " . ApiListKey::LIST_KEY->value . '.'
            );
            return false;
        }

        $this->destSr->{ApiListKey::LIST_ITEM_REPEATER_KEY->value} = $itemRepeaterKey;
        if (!$this->destSr->save()) {
            $this->addError(
                "error",
                "Error saving " . ApiListKey::LIST_ITEM_REPEATER_KEY->value . '.'
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
