<?php

namespace App\Services\ApiServices\ServiceRequests;

use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Repositories\SResponseKeyRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\Handlers\ResponseHandler;
use App\Services\ApiManager\Response\ResponseManager;
use App\Services\Tools\XmlService;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SimpleXMLIterator;

class ResponseKeyPopulateService
{
    use UserTrait, ErrorTrait;

    private SrRepository $srRepository;
    private SrResponseKeyRepository $srResponseKeyRepository;
    private SResponseKeyRepository $responseKeyRepository;
    private Sr $destSr;
    private S $destService;
    private ApiResponse $response;

    private ?array $data = [];
    private bool $overwrite = false;
    private array $findItemsArray = [];
    private array $score = [];

    public function __construct(
        private ApiRequestService $requestOperation,
        private SrConfigService   $srConfigService,
        private ResponseHandler  $responseHandler,
        private XmlService       $xmlService
    )
    {
        $this->srRepository = new SrRepository();
        $this->srResponseKeyRepository = new SrResponseKeyRepository();
        $this->responseKeyRepository = new SResponseKeyRepository();
    }

    public function run(Sr $destSr, array $sourceSrs, ?array $query = [])
    {
        $destService = $destSr->s()->first();
        if (!$destService) {
            return false;
        }

        $this->destSr = $destSr;
        $this->destService = $destService;

        $this->srRepository->addWhere('id', $sourceSrs, 'in');
        $fetchSourceSrs = $this->srRepository->findMany();

        foreach ($fetchSourceSrs as $sr) {
            $this->handleResponse(
                $sr,
                $this->runSrRequest($sr, $query)
            );
        }

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
                ResponseManager::CONTENT_TYPE_JSON => $this->handleJsonResponse($sr),
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
            'data' => $this->findByKeyTree($resultsTrack['parent'], $this->response->getRequestData(), false)
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

    private function findByKeyTree(array $data, ?array $requestData = [], ?bool $pop = true): mixed
    {
        if ($pop) {
            array_pop($data);
        }
        foreach ($data as $value) {
            array_shift($data);
            if (!isset($requestData[$value])) {
                return $requestData;
            }
            $requestData = $requestData[$value];
        }
        return $requestData;
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

    private function prepareItemsArrayScoreData(array $data, ?array $parent = []): void
    {
        $parentKey = (array_key_exists('key', $parent)) ? $parent['key'] : null;


        foreach ($data as $key => $value) {
            $parent['key'] = $key;
            if (!array_key_exists('parent', $parent)) {
                $parent['parent'] = [];
            }
            $parent['parent'][] = $key;
            if (!is_array($value)) {
                continue;
            }
            if (Arr::isAssoc($value)) {
                $parentData = $this->findByKeyTree($parent['parent'], $this->response->getRequestData());
                foreach ($value as $valKey => $val) {
                    foreach ($parentData as $values) {
                        if (!is_array($values)) {
                            continue;
                        }
                        if (array_key_exists($valKey, $values)) {
                            if (!array_key_exists($parentKey, $this->score)) {
                                $this->score[$parentKey] = [
                                    'score' => 0,
                                    'parent' => []
                                ];
                            }
                            $this->score[$parentKey]['score']++;
                            $parentTrack = $parent['parent'];
                            array_pop($parentTrack);
                            $this->score[$parentKey]['parent'] = $parentTrack;
                        }
                    }
                }
            }
            $this->prepareItemsArrayScoreData($value, $parent);
        }
    }

    private function prepareItemsArrayScoreDataForXml(SimpleXMLIterator $xmlIterator, ?array $parent = [], ?String $parentKey = null): void
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

    private function handleJsonResponse(Sr $sr)
    {
        $requestData = $this->response->getRequestData();
        if (empty($requestData)) {
            return false;
        }

        if (Arr::isList($requestData)) {
            return $this->srTypeHandler($sr, $requestData, 'root_items');
        }

        $this->prepareItemsArrayScoreData($requestData);
        $extractData = $this->extractDataFromScoreData($this->score);
        return $this->srTypeHandler($sr, $extractData['data'], $extractData['value']);
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
            $buildItemList = $this->responseHandler->buildItemListFromResponseArray($itemsArrayValue, $responseArray);
            return $this->srTypeHandler(
                $sr,
                $buildItemList[array_key_first($buildItemList)],
                $itemsArrayValue
            );
        } else {
            $simpleXMLIterator = new SimpleXmlIterator($responseContent, null, false);

            $this->prepareItemsArrayScoreDataForXml($simpleXMLIterator);
            if (Arr::isList($requestData)) {
                return $this->srTypeHandler($sr, $requestData, 'root_items');
            }

            $this->prepareItemsArrayScoreData($requestData);
            $extractData = $this->extractDataFromScoreData($this->score);

            return $this->srTypeHandler($sr, $extractData['data'], $extractData['value']);
        }
    }

    private function srTypeHandler(Sr $sr, array $data, string $itemArrayType): bool
    {
        return match ($sr->type) {
            SrRepository::SR_TYPE_LIST => $this->populateResponseKeys($data[array_key_first($data)], $itemArrayType),
            SrRepository::SR_TYPE_SINGLE, SrRepository::SR_TYPE_DETAIL => $this->populateResponseKeys($data, $itemArrayType),
            default => false,
        };
    }

    private function populateResponseKeys(array $data, string $itemArrayType): bool
    {
        if (!Arr::isAssoc($data)) {
            return false;
        }
        $responseKeys = $this->srResponseKeyRepository->findSrResponseKeysWithRelation(
            $this->destSr
        );
        $itemsArrayResponseKey = $responseKeys->firstWhere('name', 'items_array');
        if ($itemsArrayResponseKey) {
            $this->saveSrResponseKey($itemsArrayResponseKey, $itemArrayType);
        }
        foreach ($data as $key => $value) {
            $responseKey = $responseKeys->firstWhere('name', $key);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key);
                continue;
            }
            $toSnake = Str::snake($key);
            $responseKey = $responseKeys->firstWhere('name', $toSnake);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key);
                continue;
            }
            $toCamel = Str::camel($key);
            $responseKey = $responseKeys->firstWhere('name', $toCamel);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key);
                continue;
            }
            $toSlug = Str::slug($key);
            $responseKey = $responseKeys->firstWhere('name', $toSlug);
            if ($responseKey) {
                $this->saveSrResponseKey($responseKey, $key);
                continue;
            }
            $createSResponseKey = $this->responseKeyRepository->createServiceResponseKey(
                $this->destService,
                ['name' => $toSnake]
            );
            if (!$createSResponseKey) {
                continue;
            }
            $this->saveSrResponseKey($this->responseKeyRepository->getModel(), $key);
        }
        return $this->hasErrors();
    }

    private function saveSrResponseKey(SResponseKey $key, string $value): bool
    {
        $srResponseKey = $key->srResponseKey()->first();
        if ($srResponseKey && !empty($srResponseKey->value) && !$this->overwrite) {
            return false;
        }
        $save = $this->srResponseKeyRepository->saveServiceRequestResponseKey(
            $this->destSr,
            $key,
            ['value' => $value]
        );
        if (!$save) {
            $this->addError(
                'error',
                "Error saving sr response key | serviceResponseKey: {$key->name} | srResponseKeyValue: {$value}"
            );
            return false;
        }
        return true;
    }

    public function setOverwrite(bool $overwrite): void
    {
        $this->overwrite = $overwrite;
    }

    public function setData(?array $data): void
    {
        $this->data = $data;
    }

}
