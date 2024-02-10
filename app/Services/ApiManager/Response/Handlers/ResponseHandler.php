<?php

namespace App\Services\ApiManager\Response\Handlers;


use App\Models\Provider;
use App\Models\Sr;
use App\Models\SResponseKey;
use App\Models\SrResponseKey;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiServices\ServiceRequests\SrResponseKeyService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\ApiServices\SResponseKeysService;
use App\Services\Tools\XmlService;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ResponseHandler extends ApiBase
{
    protected ProviderService $providerService;
    protected SrService $requestService;
    protected SResponseKeysService $sResponseKeysService;
    protected SrResponseKeyService $srResponseKeyService;
    protected Provider $provider;
    protected Sr $apiService;
    protected XmlService $xmlService;
    protected array $responseArray;
    protected Collection $responseKeysArray;

    private string $needleMatchArrayValue = "=";
    private string $needleMatchArrayKey = ".";

    public function __construct(
        ProviderService      $providerService,
        SrService            $requestService,
        XmlService           $xmlService,
        SResponseKeysService $responseKeysService,
        SrResponseKeyService $srResponseKeyService
    )
    {
        $this->providerService = $providerService;
        $this->requestService = $requestService;
        $this->xmlService = $xmlService;
        $this->sResponseKeysService = $responseKeysService;
        $this->srResponseKeyService = $srResponseKeyService;
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

        if ($responseKeyValue === "root_items") {
            return [$this->responseArray];
        }
        if ($responseKeyValue === "root_array") {
            return $this->responseArray;
        }

        $itemsArray = array_map(function ($item) {
            $data = $this->filterItemsArrayValue($item);
            if (is_array($data) && isset($data["value"])) {
                return $data["value"];
            }
            return false;
        }, explode(".", $responseKeyValue));
        $getArrayItems = $this->getArrayItems($this->responseArray, $itemsArray);
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

    protected function buildListItems(array $itemList)
    {
        return array_map(function ($item) {
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
            return $itemList;
        }, $itemList);
    }

    protected function buildList($itemList, SrResponseKey $requestResponseKey)
    {
        $keyArray = explode(".", $requestResponseKey->value);
        $getItemValue = $this->getArrayItems($itemList, $keyArray);
        if ($requestResponseKey->is_service_request) {
            return $this->buildResponseKeyRequestItem($getItemValue, $requestResponseKey);
        } else {
            return $this->getReturnDataType($requestResponseKey, $getItemValue);
        }
    }

    protected function buildResponseKeyRequestItem($itemValue, SrResponseKey $requestResponseKey)
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
                    "request_name" => $sr->label,
                    "request_operation" => $sr->name,
                    "request_parameters" => $this->getServiceRequestParameters($sr)
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

    private function getReturnDataType(SrResponseKey $requestResponseKey, $itemArrayValue)
    {
        if (is_array($itemArrayValue)) {
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

    private function buildRequestKeyTextValue(SrResponseKey $requestResponseKey, $itemArrayValue)
    {
        if (!is_array($itemArrayValue)) {
            return $itemArrayValue;
        }
        foreach ($itemArrayValue as $item) {
            foreach ($requestResponseKey->array_keys as $arrayKey) {
                if (is_array($item)) {
                    return $this->getRequestKeyArrayItemValue($arrayKey["name"], $arrayKey["value"], $item);
                }
            }
        }
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

    protected function filterItemsArrayValue($string)
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
            case self::PARAM_FILTER_KEYS["API_BASE_URL"]['placeholder']:
                return $this->provider->api_base_url;
            default:
                return $value;
        }
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
        $this->responseKeysArray = $this->srResponseKeyService->findConfigForOperationBySr($this->apiService);
    }
}
