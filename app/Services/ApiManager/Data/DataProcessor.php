<?php

namespace App\Services\ApiManager\Data;

use App\Enums\Property\PropertyType;
use App\Models\Property;
use App\Models\Provider;
use App\Models\ProviderProperty;
use App\Models\SrConfig;
use App\Services\ApiManager\ApiBase;
use App\Services\Provider\ProviderService;
use Illuminate\Database\Eloquent\Collection;

class DataProcessor
{
    private Provider $provider;
    private array $queryArray;
    protected string $query;

    public function __construct(
        private ProviderService $providerService,
        private Collection      $requestConfigs,
        private Collection      $requestParameters,
        private Collection      $providerProperties,
    )
    {
    }

    public static function buildSingleArray(array $array, ?bool $isFirst = true)
    {
        $result = [];
        if ($isFirst && count($array)) {
            $array = [$array[array_key_first($array)]];
        }
        foreach ($array as $key => $value) {
            if ($isFirst && is_array($value)) {
                $result = array_merge($result, self::buildSingleArray($value, false));
                continue;
            }
            $result[$key] = $value;
        }
        return $result;
    }

    public static function buildListArray(array $array)
    {
        return $array;
    }

    public function filterParameterValue($paramValue)
    {
        if (preg_match_all('~\[(.*?)\]~', $paramValue, $output)) {
            foreach ($output[1] as $key => $value) {
                $filterReservedParam = $this->getReservedParamsValues($output[0][$key]);
                $paramValue = str_replace($output[0][$key], $filterReservedParam, $paramValue, $count);
            }
        }
        return $paramValue;
    }

    public function getReservedParamsValues($paramValue)
    {
        foreach (DataConstants::PARAM_FILTER_KEYS as $key => $value) {
            if ($value['placeholder'] !== $paramValue) {
                continue;
            }
            if (empty($value['keymap'])) {
                continue;
            }
            if (!empty($this->queryArray[$value['keymap']])) {
                return $this->formatValue($this->queryArray[$value['keymap']]);
            } else {
                return false;
            }
        }

        switch ($paramValue) {
            case DataConstants::PARAM_FILTER_KEYS["PROVIDER_USER_ID"]['placeholder']:
                return $this->getProviderPropertyValue(PropertyType::USER_ID->value);

            case DataConstants::PARAM_FILTER_KEYS["SECRET_KEY"]['placeholder']:
                return $this->getProviderPropertyValue(PropertyType::SECRET_KEY->value);

            case DataConstants::PARAM_FILTER_KEYS["CLIENT_ID"]['placeholder']:
                return $this->getProviderPropertyValue(PropertyType::CLIENT_ID->value);

            case DataConstants::PARAM_FILTER_KEYS["CLIENT_SECRET"]['placeholder']:
                return $this->getProviderPropertyValue(PropertyType::CLIENT_SECRET->value);

            case DataConstants::PARAM_FILTER_KEYS["ACCESS_KEY"]['placeholder']:
            case DataConstants::PARAM_FILTER_KEYS["ACCESS_TOKEN"]['placeholder']:
                return $this->getProviderPropertyValue(PropertyType::ACCESS_TOKEN->value);

            case DataConstants::PARAM_FILTER_KEYS["QUERY"]['placeholder']:
                return $this->query;

            case DataConstants::PARAM_FILTER_KEYS["TIMESTAMP"]['placeholder']:
                $date = new \DateTime();
                return $date->format("Y-m-d h:i:s");
        }
        return $this->formatValue($this->getQueryFilterValue($paramValue));
    }

    public function formatValue($value)
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        return $value;
    }

    public function getConfigValue(string $parameterName)
    {
        $srConfig = $this->getSrConfigItem($parameterName);
        if ($srConfig instanceof Property) {
            return $this->getPropertyValue($srConfig->value_type, $srConfig->srConfig);
        }

        return $this->getProviderPropertyValue($parameterName);
    }

    public function replaceListItemsOffsetPlaceholders(array $data) {
        if (is_array($data) && !array_key_exists('offset', $this->queryArray)) {
            return $this->replaceListItemsValueStr(
                DataConstants::PARAM_FILTER_KEYS['OFFSET']['placeholder'],
                $this->calculateOffset() ?? 0,
                $data
            );
        }
        return $data;
    }
    public function calculateOffset() {
        if (empty($this->queryArray['page_number'])) {
            return false;
        }
        if (empty($this->queryArray['page_size'])) {
            return false;
        }
        return (int)$this->queryArray['page_number'] * (int)$this->queryArray['page_size'];
    }

    public function replaceListItemValueStr(string $placeholder, string|int $replace, string|int $value) {

        return str_replace($placeholder, $replace, $value);
    }

    public function replaceListItemsValueStr(string $placeholder, string|int $replace, array $data) {
        foreach ($data as $key => $item) {
            if (
                is_array($data[$key]) &&
                array_key_exists('value', $data[$key])
            ) {
                $data[$key]['value'] = $this->replaceListItemValueStr($placeholder, $replace, $item['value']);
            } elseif (
                is_array($data) &&
                array_key_exists($key, $data)
            ) {
                $data[$key] = $this->replaceListItemValueStr($placeholder, $replace, $item);
            }
        }
        return $data;
    }

    public function findListItemBy(string $placeholder, string $findBy, array $data) {
        if (empty($data[$findBy])) {
            return false;
        }
        if (!str_contains($data[$findBy], $placeholder)) {
            return false;
        }
        return $data;
    }

    public function findListItemsBy(string $placeholder, string $findBy, array $data) {
        $newData = [];
        foreach ($data as $item) {
            $find = $this->findListItemBy($placeholder, $findBy, $item);
            if (!$find) {
                continue;
            }
            $newData[] = $item;
        }
        return $newData;
    }

    public function getSrConfigItem(string $parameterName)
    {
        $property = $this->requestConfigs->where('name', $parameterName)->first();
        if (!$property instanceof Property) {
            return null;
        }
        if (!$property->srConfig instanceof SrConfig) {
            return null;
        }
        return $property;
    }

    public function getQueryFilterValue($string)
    {
        if (preg_match_all('~\[(.*?)\]~', $string, $output)) {
            foreach ($output[1] as $key => $value) {
                if (!array_key_exists($value, $this->queryArray)) {
                    return false;
                }
                $queryValue = $this->queryArray[$value];
                if (is_array($queryValue)) {
                    $queryValue = implode(",", $queryValue);
                }
                $string = str_replace($output[0][$key], $queryValue, $string, $count);
            }
        }
        return $string;
    }

    public function getRequestBody()
    {
        $queryArray = [];
        foreach ($this->requestParameters as $requestParameter) {
            $queryArray[] = $this->filterParameterValue($requestParameter->value);
        }
        return implode(" ", $queryArray);
    }

    public function buildRequestQuery()
    {
        $queryArray = [];
        foreach ($this->requestParameters as $requestParameter) {
            $paramValue = $this->filterParameterValue($requestParameter->value);
            if (empty($paramValue)) {
                continue;
            }
            $value = trim($paramValue);
            if (!array_key_exists($requestParameter->name, $queryArray)) {
                $queryArray[$requestParameter->name] = $value;
                continue;
            }
            if (empty($queryArray[$requestParameter->name])) {
                $queryArray[$requestParameter->name] = $value;
                continue;
            }
            $queryArray[$requestParameter->name] = $queryArray[$requestParameter->name] . "," . $value;

        }
        return $queryArray;
    }

    public function getPropertyValue(string $valueType, ProviderProperty|SrConfig $property) {
        switch ($valueType) {
            case 'choice':
            case 'text':
                return $property->value;
            case 'list':
                return $property->array_value;
            case 'big_text':
                return $property->big_text_value;
        }
        return null;
    }

    public function getProviderPropertyValue(string $propertyName)
    {
        $property = $this->providerProperties->where('name', $propertyName)->first();
        if (!$property instanceof Property) {
            return null;
        }
        if (!$property->providerProperty instanceof ProviderProperty) {
            return null;
        }
        return $this->getPropertyValue($property->value_type, $property->providerProperty);
    }

    public function setRequestConfigs(Collection $requestConfigs): void
    {
        $this->requestConfigs = $requestConfigs;
    }

    public function getRequestConfigs(): Collection
    {
        return $this->requestConfigs;
    }

    public function setRequestParameters(Collection $requestParameters): void
    {
        $this->requestParameters = $requestParameters;
    }

    public function setQueryArray(array $queryArray): void
    {
        $this->queryArray = $queryArray;
    }

    public function setQuery(string $query): void
    {
        $this->query = $query;
    }

    public function setProvider(Provider $provider): void
    {
        $this->provider = $provider;
    }

    public function setProviderProperties(Collection $providerProperties): void
    {
        $this->providerProperties = $providerProperties;
    }
    public function getProviderProperties(): Collection
    {
        return $this->providerProperties;
    }

}
