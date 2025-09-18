<?php

namespace App\Services\ApiManager\Response\Handlers;


use App\Helpers\Tools\DateHelpers;
use App\Models\Provider;
use App\Models\S;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Models\SrResponseKeySr;
use App\Repositories\SrRepository;
use App\Repositories\SrResponseKeySrRepository;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Operations\ApiRequestService;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiServices\ServiceRequests\ResponseKeys\SrResponseKeyService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\Provider\ProviderService;
use App\Services\Tools\XmlService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\App;
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
    ) {}

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

        $srResponseKeySrs = SrResponseKeySr::query()
            ->whereHas('srResponseKey', function ($query) {
                $query->where('sr_id', $this->apiService->id);
            })
            ->get();

        foreach ($srResponseKeySrs as $srResponseKeySr) {
            $sResponseKey = $srResponseKeySr->srResponseKey->sResponseKey;

            foreach ($buildItems as $index => $data) {
                $response = $this->hasReturnValueResponseKeySrs($srResponseKeySr, $data);
                if (!empty($response)) {
                    $buildItems[$index][$sResponseKey->name] = $response;
                } else {
                    $buildItems[$index][$sResponseKey->name] = $data[$sResponseKey->name] ?? null;
                }
            }
        }
        return $buildItems;
    }

    protected function buildList($itemList, SrResponseKey $requestResponseKey)
    {
        $keyArray = explode(".", $requestResponseKey->value);
        $getItemValue = $this->getArrayItems($itemList, $keyArray);
        return $this->getReturnDataType($requestResponseKey, $getItemValue);
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
        $newDate = DateHelpers::parseDateString($value, $requestResponseKey->date_format);
        if ($newDate) {
            return $newDate;
        }
        return $value;
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
        if (
            $requestResponseKey->is_date ||
            (
                !empty($itemArrayValue) &&
                !is_numeric($itemArrayValue) &&
                DateHelpers::isValidDateString($itemArrayValue)
            )
        ) {
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
                if (
                    array_key_exists($compareKey, $arrayItem) &&
                    $arrayItem[$compareKey] == $compareValue
                ) {
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

    private function hasReturnValueResponseKeySrs(
        SrResponseKeySr $srResponseKeySr,
        array $data
    ) {
        $sResponseKey = $srResponseKeySr->srResponseKey->sResponseKey;

        $keyReqResponseValue = $data[$sResponseKey->name] ?? null;

        if (
            $srResponseKeySr->action !== SrResponseKeySrRepository::ACTION_RETURN_VALUE
        ) {
            return $keyReqResponseValue;
        }

        $singleRequest = $srResponseKeySr->single_request ?? false;
        $disableRequest = $srResponseKeySr->disable_request ?? false;
        if ($disableRequest) {
            return $keyReqResponseValue;
        }

        $sr = $srResponseKeySr?->sr;
        if (!$sr instanceof Sr) {
            return false;
        }

        $provider = $srResponseKeySr->sr?->provider;
        if (!$provider instanceof Provider) {
            return false;
        }

        $requestResponseKeys = $srResponseKeySr?->request_response_keys ?? [];
        $responseResponseKeys = $srResponseKeySr?->response_response_keys ?? [];

        $returnValue = null;
        if (is_array($keyReqResponseValue)) {
            $returnValue = [];
            foreach ($keyReqResponseValue as $key => $value) {
                if (!is_string($value) && !is_numeric($value)) {
                    continue;
                }

                $queryData = $this->buildNestedSrResponseKeyData(
                    $requestResponseKeys,
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
                        $responseResponseKeys
                    )
                );

                if ($singleRequest) {
                    break;
                }
            }
        } elseif (
            is_string($keyReqResponseValue) ||
            is_numeric($keyReqResponseValue)
        ) {
            $queryData = $this->buildNestedSrResponseKeyData(
                $requestResponseKeys,
                $keyReqResponseValue,
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
                $responseResponseKeys
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
