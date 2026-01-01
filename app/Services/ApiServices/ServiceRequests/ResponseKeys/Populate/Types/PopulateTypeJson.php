<?php

namespace App\Services\ApiServices\ServiceRequests\ResponseKeys\Populate\Types;

use Truvoicer\TfDbReadCore\Enums\Api\ApiListKey;
use Truvoicer\TfDbReadCore\Enums\Sr\SrType;
use Truvoicer\TfDbReadCore\Models\Sr;
use Truvoicer\TfDbReadCore\Services\ApiManager\Data\DataConstants;
use Truvoicer\TfDbReadCore\Services\ApiManager\Operations\ApiRequestService;
use Truvoicer\TfDbReadCore\Services\ApiManager\Response\Entity\ApiDetailedResponse;
use Truvoicer\TfDbReadCore\Services\ApiManager\Response\Handlers\ResponseHandler;
use Truvoicer\TfDbReadCore\Services\ApiManager\Response\ResponseManager;
use Truvoicer\TfDbReadCore\Services\ApiServices\ServiceRequests\SrConfigService;
use Illuminate\Support\Arr;

class PopulateTypeJson extends PopulateTypeBase
{

    public function __construct(
        protected ApiRequestService $requestOperation,
        protected SrConfigService   $srConfigService,
        protected ResponseHandler   $responseHandler
    ) {
        parent::__construct($requestOperation, $srConfigService, $responseHandler);
        $this->setReservedKeys(
            array_column(
                DataConstants::JSON_SERVICE_RESPONSE_KEYS,
                DataConstants::RESPONSE_KEY_NAME
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
            'value' => $itemsArrayData['list_key_value'],
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
                'list_key_value' => 'root_array',
                'value' => $value
            ];
        } elseif (is_string($value)) {
            return [
                'list_key_value' => $value,
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

        if (
            !empty($this->data['list_key']) &&
            $this->data['list_key'] === 'root_item'
        ) {
            return $this->srTypeHandler($sr, $requestData);
        }
        if (
            !empty($this->data['list_key']) &&
            $this->data['list_key'] === 'root_array'
        ) {
            return $this->srTypeHandler(
                $sr,
                $requestData
            );
        }

        if (Arr::isList($requestData)) {
            $this->destSr->{ApiListKey::LIST_KEY->value} = 'root_items';
            if (!$this->destSr->save()) {
                $this->addError(
                    "error",
                    "Error saving " . ApiListKey::LIST_KEY->value . '.'
                );
                return false;
            }
            return $this->srTypeHandler($sr, $requestData);
        }


        $this->prepareItemsArrayScoreData($requestData);
        $extractData = $this->extractDataFromScoreData($this->score);
        $this->destSr->{ApiListKey::LIST_KEY->value} = $extractData['value'];
        if (!$this->destSr->save()) {
            $this->addError(
                "error",
                "Error saving " . ApiListKey::LIST_KEY->value . '.'
            );
            return false;
        }
        return $this->srTypeHandler($sr, $extractData['data']);
    }

    private function srTypeHandler(Sr $sr, array $data): bool
    {
        return match ($sr->type) {
            SrType::LIST => $this->populateResponseKeys(
                $data[array_key_first($data)]
            ),
            SrType::SINGLE, SrType::DETAIL => $this->populateResponseKeys($data),
            default => false,
        };
    }
}
