<?php

namespace App\Services\ApiManager\Response\Handlers;


use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeySrRepository;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ServiceRequests\SrResponseKeyService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\Tools\XmlService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ResponseHandler extends ApiBase
{
    protected Provider $provider;
    protected Sr $apiService;
    protected array $responseArray;
    protected Collection $responseKeysArray;

    private string $needleMatchArrayValue = "=";
    private string $needleMatchArrayKey = ".";

    public function __construct(
        protected ProviderService      $providerService,
        protected SrService            $requestService,
        protected XmlService           $xmlService,
        protected SResponseKeysService $responseKeysService,
        protected SrResponseKeyService $srResponseKeyService,
    )
    {
    }

    protected function findSrResponseKeyValueInArray(string $name)
    {
        $responseKey = $this->responseKeysArray->where("name", $name)->first();
        if (!$responseKey instanceof SResponseKey || !$responseKey->srResponseKey instanceof SrResponseKey) {
            return null;
        }
        return $responseKey->srResponseKey->value;
    }

    protected function getItemList()
    {
        $responseKeyValue = $this->findSrResponseKeyValueInArray('items_array');
        if (empty($responseKeyValue)) {
            throw new BadRequestHttpException("Response key value is empty.");
        }
        return $this->buildItemListFromResponseArray($responseKeyValue, $this->responseArray);
    }

    public function buildItemListFromResponseArray(string $itemsArrayValue, array $responseArray)
    {
        if ($itemsArrayValue === "root_items") {
            return [$responseArray];
        }
        if ($itemsArrayValue === "root_array") {
            return $responseArray;
        }

        $itemsArray = array_map(function ($item) {
            $data = $this->filterItemsArrayValue($item);
            if (is_array($data) && isset($data["value"])) {
                return $data["value"];
            }
            return false;
        }, explode(".", $itemsArrayValue));
        $getArrayItems = $this->getArrayItems($responseArray, $itemsArray);
        if ($getArrayItems === "") {
            throw new BadRequestHttpException("Items list is empty");
        }

        return array_filter((array)$getArrayItems, function ($item) {
            return is_array($item);
        });

    }

    protected function getParentItemList()
    {
        $array = [];
        $itemsArrayString = $this->findSrResponseKeyValueInArray('items_array');
        if ($itemsArrayString !== null) {
            foreach ($this->responseArray as $key => $value) {
                if ($key !== $itemsArrayString) {
                    $itemsArray = explode(".", $key);
                    $array[$key] = $this->getArrayItems($this->responseArray, $itemsArray);
                }
            }
        }
        return $array;
    }

    protected function buildParentListItems(array $itemList)
    {
        $buildList = [];
        foreach ($this->responseKeysArray as $key) {
            $srResponseKey = $key->srResponseKey()->first();
            if (!$srResponseKey instanceof SrResponseKey) {
                continue;
            }
            if (!$srResponseKey->exists) {
                continue;
            }
            if (!$srResponseKey->list_item && $srResponseKey->show_in_response) {
                $buildList[$key->name] = $this->buildList($itemList, $srResponseKey);
            }

        }
        return $buildList;
    }

    protected function buildListItems(array $itemList): array
    {
        $buildItems = array_map(function ($item) {
            $itemList = [];
            foreach ($this->responseKeysArray as $responseKey) {
                if (!$responseKey->srResponseKey instanceof SrResponseKey) {
                    continue;
                }
                if (!$responseKey->srResponseKey->list_item) {
                    continue;
                }
                $name = $responseKey->name;
                $srResponseKey = $responseKey->srResponseKey;
                if ($srResponseKey->show_in_response) {
                    $itemList[$name] = $this->buildList($item, $srResponseKey);
                }

            }
            $itemList["provider"] = $this->provider->name;
            $itemList["requestCategory"] = $this->apiService->category->name;
            $itemList["serviceRequest"] = $this->apiService->name;
            $itemList["service"] = $this->apiService->s()->first()->only('id', 'name');
            return $itemList;
        }, $itemList);

        foreach ($buildItems as $index => $data) {
            foreach ($data as $key => $items) {
                $validate = $this->validateResponseKeySrConfig($items);
                if (!$validate) {
                    continue;
                }
                foreach ($validate as  $item) {
                    $response = $this->hasReturnValueResponseKeySrs($item, $data);
                    if (!empty($response)) {
                        $buildItems[$index][$key] = $response;
                    } else {
                        $buildItems[$index][$key] = $item;
                    }
                }
            }
        }
        return $buildItems;
    }

    protected function buildList($itemList, SrResponseKey $requestResponseKey)
    {
        $keyArray = explode(".", $requestResponseKey->value);
        $getItemValue = $this->getArrayItems($itemList, $keyArray);
        if ($requestResponseKey->is_service_request) {
            return $this->buildResponseKeyRequestItem($getItemValue, $requestResponseKey, $itemList);
        } else {
            return $this->getReturnDataType($requestResponseKey, $getItemValue);
        }
    }

    protected function buildResponseKeyRequestItem($itemValue, SrResponseKey $requestResponseKey, $itemList)
    {
        if ($requestResponseKey->srResponseKeySrs()->get()->count() === 0) {
            return null;
        }
        $data = $this->getReturnDataType($requestResponseKey, $itemValue);

        $srs = $requestResponseKey->srResponseKeySrs()->get();
        $items = [];
        foreach ($srs as $sr) {
            $items[] = [
                "data" => $data,
                "request_item" => [
                    "request_label" => $sr->label,
                    "request_name" => $sr->name,
                    "request_type" => $sr->type,
                    "request_parameters" => $this->getServiceRequestParameters($sr),
                    'action' => $sr?->pivot?->action,
                    'single_request' => $sr?->pivot?->single_request,
                    'disable_request' => $sr?->pivot?->disable_request,
                    'request_response_keys' => $sr?->pivot?->request_response_keys,
                    'response_response_keys' => $sr?->pivot?->response_response_keys,
                    'provider_name' => $sr?->provider?->name,
                    'provider_label' => $sr?->provider?->label,
                ]
            ];
        }
        return $items;
    }

    private function getServiceRequestParameters(Sr $serviceRequest)
    {
        $array = [];
        $parameters = $serviceRequest->srParameter()->get();
        foreach ($parameters as $parameter) {
            array_push($array, $parameter->name);
        }
        return $array;
    }

    protected function buildResponseKeyValue($keyValue, SrResponseKey $requestResponseKey)
    {
        $responseKeyValue = $keyValue;

        if ($requestResponseKey->custom_value) {
            $responseKeyValue = $requestResponseKey->value;
        }
        if (!empty($requestResponseKey->prepend_extra_data_value)) {
            $responseKeyValue = $requestResponseKey->prepend_extra_data_value . $responseKeyValue;
        }
        if (!empty($requestResponseKey->append_extra_data_value)) {
            $responseKeyValue = $responseKeyValue . $requestResponseKey->append_extra_data_value;
        }

        return $this->replaceReservedResponseKeyValue($responseKeyValue);
    }

    protected function getArrayItems($arrayItems, $queryArray)
    {
        foreach ($queryArray as $value) {
            $item = $value;
            unset($queryArray[array_search($value, $queryArray)]);
            if (!is_array($arrayItems) || !array_key_exists($value, $arrayItems)) {
                return "";
            }
            return $this->getArrayItems($arrayItems[$item], $queryArray);
        }
        return $arrayItems;
    }

    protected function getRequestKeyArrayValue($itemArrayValue, SrResponseKey $requestResponseKey = null)
    {
        if ($requestResponseKey === null || !is_array($itemArrayValue)) {
            return null;
        }
        return $this->getReturnDataType($requestResponseKey, $itemArrayValue);
    }

    protected function hasAttributeValue(SrResponseKey $requestResponseKey, $itemArrayValue)
    {
        if (!is_array($itemArrayValue)) {
            return false;
        }
        $fields = ['xml_value_type', 'attributes', 'values'];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $itemArrayValue)) {
                return false;
            }
        }
        return ($itemArrayValue['xml_value_type'] === 'attribute');
    }

    protected function buildAttributeValue(SrResponseKey $requestResponseKey, $itemArrayValue)
    {
        $values = [];
        if (array_key_exists('values', $itemArrayValue)) {
            $values = $itemArrayValue['values'];
        }
        return $this->getReturnDataType($requestResponseKey, $values);
    }

    private function getReturnDataType(SrResponseKey $requestResponseKey, $itemArrayValue)
    {
        if ($this->hasAttributeValue($requestResponseKey, $itemArrayValue)) {
            return $this->buildAttributeValue($requestResponseKey, $itemArrayValue);
        } else if (is_array($itemArrayValue)) {
            return $this->buildRequestKeyArrayValue($requestResponseKey, $itemArrayValue);
        } else if (is_object($itemArrayValue)) {
            return $this->buildRequestKeyObjectValue($requestResponseKey, $itemArrayValue);
        }
        return $this->buildResponseKeyValue(
            $this->buildRequestKeyTextValue($requestResponseKey, $itemArrayValue),
            $requestResponseKey
        );
    }

    private function buildRequestKeyArrayValue(SrResponseKey $requestResponseKey, $itemArrayValue)
    {
        if (empty($requestResponseKey->array_keys) || !is_array($requestResponseKey->array_keys) || count($requestResponseKey->array_keys) === 0) {
            return $itemArrayValue;
        }
        $buildArray = [];
        foreach ($itemArrayValue as $item) {
            $array = [];
            foreach ($requestResponseKey->array_keys as $arrayKey) {
                if (is_array($item)) {
                    $array[$arrayKey["name"]] = $this->getRequestKeyArrayItemValue($arrayKey["name"], $arrayKey["value"], $item);
                }
            }
            if (count($array) > 0) {
                $buildArray[] = $array;
            }
        }
        return $buildArray;
    }

    private function buildRequestKeyObjectValue(SrResponseKey $requestResponseKey, $itemArrayValue)
    {
        $buildArray = [];
        foreach ($itemArrayValue as $item) {
            foreach ($requestResponseKey->array_keys as $arrayKey) {
                if (is_array($item)) {
                    $buildArray[$arrayKey["name"]] = $this->getRequestKeyArrayItemValue($arrayKey["name"], $arrayKey["value"], $item);
                }
            }
        }
        return $buildArray;
    }

    private function buildDateValue(SrResponseKey $requestResponseKey, $value)
    {
        if (empty($requestResponseKey->date_format)) {
            return Carbon::create($value)->toISOString();
        }
        return Carbon::createFromFormat($requestResponseKey->date_format, $value)->toISOString();
    }

    private function buildRequestKeyTextValue(SrResponseKey $requestResponseKey, $itemArrayValue)
    {
        if (is_array($itemArrayValue)) {
            foreach ($itemArrayValue as $item) {
                foreach ($requestResponseKey->array_keys as $arrayKey) {
                    if (is_array($item)) {
                        return $this->getRequestKeyArrayItemValue($arrayKey["name"], $arrayKey["value"], $item);
                    }
                }
            }
            return null;
        }
        if ($requestResponseKey->is_date) {
            return $this->buildDateValue($requestResponseKey, $itemArrayValue);
        }
        return $itemArrayValue;
    }

    private function getRequestKeyArrayItemValue($objectKeyToReturn, $conditions, $item)
    {
        if (strpos($conditions, $this->needleMatchArrayValue) !== false) {
            $needle = $this->needleMatchArrayValue;
        } else if (strpos($conditions, $this->needleMatchArrayKey) !== false) {
            $needle = $this->needleMatchArrayKey;
        } else {
            if (array_key_exists($conditions, $item)) {
                return $item[$conditions];
            }
            return null;
        }
        $itemsArray = explode($needle, $conditions);
        switch ($needle) {
            case $this->needleMatchArrayValue:
                $compareValue = $itemsArray[1];
                $key = explode($this->needleMatchArrayKey, $itemsArray[0]);
                if (count($key) > 1) {
                    $compareKey = $key[count($key) - 1];
                    unset($key[count($key) - 1]);
                } else {
                    $compareKey = $key[0];
                }
                $arrayItem = $this->getArrayItems($item, $key);
                if (array_key_exists($compareKey, $arrayItem) &&
                    $arrayItem[$compareKey] == $compareValue) {
                    return $arrayItem[$objectKeyToReturn];
                }
                break;
            case $this->needleMatchArrayKey:
                return $this->getArrayItems($item, $itemsArray);
        }
        return false;
    }

    public function filterItemsArrayValue($string)
    {
        if (preg_match_all('~\[(.*?)\]~', $string, $output)) {
            return [
                "value" => $output[1][0],
                "brackets" => true
            ];
        }
        return [
            "value" => $string,
            "brackets" => false
        ];
    }

    private function replaceReservedResponseKeyValue($string)
    {
        if (!is_string($string)) {
            return $string;
        }
        if (preg_match_all('~\[(.*?)\]~', $string, $output)) {
            foreach ($output[0] as $key => $value) {
                $string = str_replace($output[0][$key], $this->getReservedParamsValues($value), $string);
            }
        }
        return $string;
    }

    private function getReservedParamsValues($value)
    {
        switch ($value) {
            case DataConstants::PARAM_FILTER_KEYS["API_BASE_URL"]['placeholder']:
            case DataConstants::PARAM_FILTER_KEYS["BASE_URL"]['placeholder']:
                return $this->providerService->getProviderPropertyValue($this->provider, DataConstants::BASE_URL);
            default:
                return $value;
        }
    }

    private function getRequestResponseKeyNames(array $data)
    {
        if (
            !empty($data['request_response_keys']) &&
            is_array($data['request_response_keys'])
        ) {
            return $data['request_response_keys'];
        }
        return [];
    }

    private function getResponseResponseKeyNames(array $data)
    {
        if (
            !empty($data['response_response_keys']) &&
            is_array($data['response_response_keys'])
        ) {
            return $data['response_response_keys'];
        }
        return [];
    }

    private function buildNestedSrResponseKeyData(array $responseKeyNames, string|int $value, array $data)
    {
        $buildData = array_filter($data, function ($key) use ($responseKeyNames) {
            return in_array($key, $responseKeyNames);
        }, ARRAY_FILTER_USE_KEY);
        $buildData['item_id'] = $value;
        return $buildData;
    }

    private function buildReturnValue(Sr $sr, array $data, array $responseKeyNames)
    {
        if (!count($responseKeyNames)) {
            return null;
        }
        switch ($sr->type) {
            case SrRepository::SR_TYPE_DETAIL:
            case SrRepository::SR_TYPE_SINGLE:
                if (count($responseKeyNames) === 1) {
                    return $data[$responseKeyNames[0]];
                }
                return array_filter($data, function ($key) use ($responseKeyNames) {
                    return in_array($key, $responseKeyNames);
                }, ARRAY_FILTER_USE_KEY);

            case SrRepository::SR_TYPE_LIST:
                return array_map(function ($item) use ($responseKeyNames) {
                    if (count($responseKeyNames) === 1) {
                        return $item[$responseKeyNames[0]];
                    }
                    return array_filter($item, function ($key) use ($responseKeyNames) {
                        return in_array($key, $responseKeyNames);
                    }, ARRAY_FILTER_USE_KEY);
                }, $data);
            default:
                return null;
        }
    }

    private function validateResponseKeySrConfig($data)
    {
        if (!is_array($data)) {
            return false;
        }
        return array_filter($data, function ($item) {
            if (!is_array($item)) {
                return false;
            }

            if (
                !Arr::exists($item, 'data') &&
                !Arr::exists($item, 'request_item')
            ) {
                return false;
            }
            if (!array_key_exists('request_item', $item)) {
                return false;
            }
            if (!is_array($item['request_item'])) {
                return false;
            }
            $requestItem = $item['request_item'];
            if (empty($requestItem['request_name'])) {
                return false;
            }
            if (empty($requestItem['provider_name'])) {
                return false;
            }
            if (empty($requestItem['action'])) {
                return false;
            }
            return $requestItem['action'] === SrResponseKeySrRepository::ACTION_RETURN_VALUE;
        });
    }

    private function srOperationResponseHandler(Sr $sr, ApiResponse $apiResponse)
    {
        $provider = $sr->provider()->first();
        if ($apiResponse->getStatus() !== 'success') {
            return false;
        }
        $service = $sr->s()->first();
        if (!$service instanceof S) {
            return false;
        }
        $requestData = $apiResponse->getRequestData();
        if (count($requestData) === 0) {
            return false;
        }
        return $apiResponse;
    }

    private function executeSrOperationRequest(Sr $sr, ?array $queryData = ['query' => ''])
    {
        $requestOperation = App::make(ApiRequestService::class);
        $provider = $sr->provider()->first();
        if (!$provider instanceof Provider) {
            return false;
        }

        $requestOperation->setProvider($provider);
        if ($this->user->cannot('view', $provider)) {
            return false;
        }
        $requestOperation->setSr($sr);
        $requestOperation->setUser($this->user);

        $apiResponse = $this->srOperationResponseHandler(
            $sr,
            $requestOperation->runOperation($queryData)
        );
        if (!$apiResponse) {
            return false;
        }

        return $apiResponse->getRequestData();
    }

    private function hasReturnValueResponseKeySrs($item, $data)
    {
        $requestItem = $item['request_item'];
        $singleRequest = $requestItem['single_request'] ?? false;
        $disableRequest = $requestItem['disable_request'] ?? false;
        if ($disableRequest) {
            return $item;
        }
        $provider = $this->providerService->getUserProviderByName($this->user, $requestItem['provider_name']);
        if (!$provider instanceof Provider) {
            return false;
        }
        $sr = SrRepository::getSrByName($provider, $requestItem['request_name']);
        if (!$sr instanceof Sr) {
            return false;
        }

        $responseKeyNames = $this->getResponseResponseKeyNames($requestItem);
        $srConfigData = $item['data'];

        $returnValue = null;
        if (is_array($srConfigData)) {
            $returnValue = [];
            foreach ($srConfigData as $key => $value) {
                if (!is_string($value) && !is_numeric($value)) {
                    continue;
                }

                $queryData = $this->buildNestedSrResponseKeyData(
                    $this->getRequestResponseKeyNames($requestItem),
                    $value,
                    $data
                );

                $response = $this->executeSrOperationRequest(
                    $sr,
                    $queryData
                );
                if (!$response) {
                    continue;
                }
                $returnValue = array_merge(
                    $returnValue,
                    $this->buildReturnValue(
                        $sr,
                        $response,
                        $responseKeyNames
                    )
                );

                if ($singleRequest) {
                    break;
                }
            }

        } elseif (is_string($srConfigData)) {
            $queryData = $this->buildNestedSrResponseKeyData(
                $this->getRequestResponseKeyNames($requestItem),
                $srConfigData,
                $data
            );
            $response = $this->executeSrOperationRequest(
                $sr,
                $queryData
            );
            if (!$response) {
                return false;
            }
            $returnValue = $this->buildReturnValue(
                $sr,
                $response,
                $responseKeyNames
            );
        }

        return $returnValue;
    }

    /**
     * @return mixed
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param mixed $provider
     */
    public function setProvider($provider): void
    {
        $this->provider = $provider;
    }

    /**
     * @return mixed
     */
    public function getApiService()
    {
        return $this->apiService;
    }

    /**
     * @param mixed $apiService
     */
    public function setApiService(Sr $apiService): void
    {
        $this->apiService = $apiService;
    }

    /**
     * @return mixed
     */
    public function getResponseArray()
    {
        return $this->responseArray;
    }

    public function buildResponseKeysArray(): array
    {
        $buildArray = [];
        foreach ($this->responseKeysArray as $key => $responseKey) {
            if (!$responseKey->srResponseKey instanceof SrResponseKey) {
                continue;
            }
            $buildArray[$responseKey->name] = $responseKey->srResponseKey->value;
        }
        return $buildArray;
    }

    public function setResponseKeysArray(): void
    {
        $this->responseKeysArray = $this->srResponseKeyService->findResponseKeysForOperationBySr($this->apiService);
    }
}
