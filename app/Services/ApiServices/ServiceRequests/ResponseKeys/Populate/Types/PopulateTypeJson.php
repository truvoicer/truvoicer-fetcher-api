<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types;

use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Repositories\SResponseKeyRepository;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeyRepository;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\Handlers\ResponseHandler;
use App\Services\ApiManager\Response\ResponseManager;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\Tools\XmlService;
use App\Traits\Error\ErrorTrait;
use App\Traits\User\UserTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use SimpleXMLIterator;

class PopulateTypeJson extends PopulateTypeBase
{

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

    private function handleJsonResponse(Sr $sr)
    {
        $requestData = $this->response->getRequestData();
        if (empty($requestData)) {
            return false;
        }

        if (Arr::isList($requestData)) {
            if (!$this->saveSrResponseKey('items_array', 'root_items')) {
                $this->addError(
                    "error",
                    "Error saving items_array response key."
                );
                return false;
            }
            return $this->srTypeHandler($sr, $requestData);
        }

        $this->prepareItemsArrayScoreData($requestData);
        $extractData = $this->extractDataFromScoreData($this->score);
        if (!$this->saveSrResponseKey('items_array', $extractData['value'])) {
            $this->addError(
                "error",
                "Error saving items_array response key."
            );
            return false;
        }
        return $this->srTypeHandler($sr, $extractData['data']);
    }

    private function srTypeHandler(Sr $sr, array $data): bool
    {
        return match ($sr->type) {
            SrRepository::SR_TYPE_LIST => $this->populateResponseKeys($data[array_key_first($data)]),
            SrRepository::SR_TYPE_SINGLE, SrRepository::SR_TYPE_DETAIL => $this->populateResponseKeys($data),
            default => false,
        };
    }
}
