<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types;

use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Repositories\SResponseKeyRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Data\DefaultData;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\Handlers\ResponseHandler;
use App\Services\ApiManager\Response\ResponseManager;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\Tools\XmlService;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SimpleXMLIterator;

class PopulateTypeXml extends PopulateTypeBase
{
    public function __construct(
        protected ApiRequestService $requestOperation,
        protected SrConfigService   $srConfigService,
        protected ResponseHandler   $responseHandler,
        protected XmlService        $xmlService
    )
    {
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


    public function runSrRequest(Sr $sr, ?array $query = []): ApiResponse
    {
        $provider = $sr->provider()->first();
        $this->requestOperation->setProviderName($provider->name);
        $this->requestOperation->setApiRequestName($sr->name);
        $this->requestOperation->setUser($this->getUser());
        return $this->requestOperation->getOperationRequestContent('raw', $query);
    }

    public function handleResponse(Sr $sr, ApiResponse $response): bool
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


    private function extractDataFromScoreData(array $scoreData): array
    {
        $itemsArrayData = $this->getItemsArrayValueFromScoreData($scoreData);
        $resultsTrack = $scoreData[$itemsArrayData['value']];

        return [
            'value' => $itemsArrayData['items_array_value'],
            'data' => $this->findByKeyTreeForXml($resultsTrack['parent'], $this->response->getRequestData(), false)
        ];
    }

    private function getItemsArrayValueFromScoreData(array $scoreData): array|bool
    {
        if (empty($scoreData)) {
            return false;
        }
        uasort($scoreData, function ($a, $b) {
            if ($a['score'] == $b['score']) {
                return 0;
            }
            return ($a['score'] < $b['score']) ? -1 : 1;
        });
        $value = array_keys($scoreData)[0];
        if (is_integer($value)) {
            return [
                'items_array_value' => 'root_array',
                'value' => $value
            ];
        } elseif (is_string($value)) {
            return [
                'items_array_value' => $value,
                'value' => $value
            ];
        }
        return false;
    }

    private function findByKeyTreeForXml(array $data, SimpleXMLIterator $xmlIterator, ?bool $pop = true): mixed
    {
//        if ($pop) {
//            array_pop($data);
//        }

        foreach ($data as $key => $value) {
            for ($xmlIterator->rewind(); $xmlIterator->valid(); $xmlIterator->next()) {
                array_shift($data);
                if ($xmlIterator->key() === $value) {
                    return $xmlIterator->current();
                }
            }
        }
        return false;
    }

    private function prepareItemsArrayScoreDataForXml(SimpleXMLIterator $xmlIterator, ?array $parent = [], ?string $parentKey = null): void
    {
        $parentKey = (array_key_exists('key', $parent)) ? $parent['key'] : null;
        for ($xmlIterator->rewind(); $xmlIterator->valid(); $xmlIterator->next()) {
            $parent['key'] = $xmlIterator->key();
            if (!$xmlIterator->hasChildren()) {
                continue;
            }
            if (!array_key_exists('parent', $parent)) {
                $parent['parent'] = [];
            }
            if ($parentKey) {
                $parent['parent'][] = $parentKey;
            }
            $value = (array)$xmlIterator->current();
            if (Arr::isAssoc($value)) {
                $parentData = $this->findByKeyTreeForXml(
                    $parent['parent'],
                    new SimpleXMLIterator(
                        $this->response->getResponse()->body()
                    )
                );
                $parentData = (array)$parentData;
//                dd($parentData, $value, $parent['parent']);
//
                foreach ($value as $valKey => $val) {
                    if ($valKey === 'pubDate') {
                        dd($parentKey, $value, $parent, $parentData);
                    }
//                    foreach ($parentData as $values) {
//                        if (!is_array($values)) {
//                            continue;
//                        }
//                        if (array_key_exists($valKey, $values)) {
//                            if (!array_key_exists($parentKey, $this->score)) {
//                                $this->score[$parentKey] = [
//                                    'score' => 0,
//                                    'parent' => []
//                                ];
//                            }
//                            $this->score[$parentKey]['score']++;
//                            $parentTrack = $parent['parent'];
//                            array_pop($parentTrack);
//                            $this->score[$parentKey]['parent'] = $parentTrack;
//                        }
//                    }
                }
                $this->prepareItemsArrayScoreDataForXml($xmlIterator->current(), $parent, $xmlIterator->key());
            }
        }
    }

    private function handleXmlResponse(Sr $sr)
    {
        $responseContent = $this->response->getResponse()->body();
        if (empty($responseContent)) {
            return false;
        }
        if (!empty($this->data['items_array']) && !empty($this->data['item_repeater_key'])) {
            $itemsArrayValue = $this->data['items_array'];
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
            if (!$this->saveSrResponseKeyByName('items_array', $itemsArrayValue)) {
                $this->addError(
                    "error",
                    "Error saving items_array response key."
                );
                return false;
            }

            if (!$this->saveSrResponseKeyByName('item_repeater_key', $itemRepeaterKey)) {
                $this->addError(
                    "error",
                    "Error saving item_repeater_key response key."
                );
                return false;
            }

            return $this->srTypeHandler(
                $sr,
                $buildItemList,
                $itemsArrayValue
            );
        } else {
            $simpleXMLIterator = new SimpleXmlIterator($responseContent, null, false);

            $this->prepareItemsArrayScoreDataForXml($simpleXMLIterator);
            if (Arr::isList($requestData)) {
                return $this->srTypeHandler($sr, $requestData, 'root_items');
            }

            $extractData = $this->extractDataFromScoreData($this->score);

            return $this->srTypeHandler($sr, $extractData['data'], $extractData['value']);
        }
    }

    private function srTypeHandler(Sr $sr, array $data, string $itemArrayType): bool
    {
        return match ($sr->type) {
            SrRepository::SR_TYPE_LIST => $this->populateResponseKeys($data[array_key_first($data)]),
            SrRepository::SR_TYPE_SINGLE, SrRepository::SR_TYPE_DETAIL => $this->populateResponseKeys($data),
            default => false,
        };
    }


}
