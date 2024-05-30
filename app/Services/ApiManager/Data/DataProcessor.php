<?php

namespace App\Services\ApiManager\Data;

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
        foreach (ApiBase::PARAM_FILTER_KEYS as $key => $value) {
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
            case ApiBase::PARAM_FILTER_KEYS["OAUTH_GRANT_TYPE_FIELD_NAME"]['placeholder']:
                return $this->getProviderPropertyValue(ApiBase::OAUTH_GRANT_TYPE_FIELD_NAME);

            case ApiBase::PARAM_FILTER_KEYS["OAUTH_GRANT_TYPE_FIELD_VALUE"]['placeholder']:
                return $this->getProviderPropertyValue(ApiBase::OAUTH_GRANT_TYPE_FIELD_VALUE);

            case ApiBase::PARAM_FILTER_KEYS["OAUTH_SCOPE_FIELD_NAME"]['placeholder']:
                return $this->getProviderPropertyValue(ApiBase::OAUTH_SCOPE_FIELD_NAME);

            case ApiBase::PARAM_FILTER_KEYS["OAUTH_SCOPE_FIELD_VALUE"]['placeholder']:
                return $this->getProviderPropertyValue(ApiBase::OAUTH_SCOPE_FIELD_VALUE);

            case ApiBase::PARAM_FILTER_KEYS["PROVIDER_USER_ID"]['placeholder']:
                return $this->getProviderPropertyValue(ApiBase::USER_ID);

            case ApiBase::PARAM_FILTER_KEYS["SECRET_KEY"]['placeholder']:
                return $this->getProviderPropertyValue(ApiBase::SECRET_KEY);

            case ApiBase::PARAM_FILTER_KEYS["CLIENT_ID"]['placeholder']:
                return $this->getProviderPropertyValue(ApiBase::CLIENT_ID);

            case ApiBase::PARAM_FILTER_KEYS["CLIENT_SECRET"]['placeholder']:
                return $this->getProviderPropertyValue(ApiBase::CLIENT_SECRET);

            case ApiBase::PARAM_FILTER_KEYS["ACCESS_KEY"]['placeholder']:
            case ApiBase::PARAM_FILTER_KEYS["ACCESS_TOKEN"]['placeholder']:
                return $this->getProviderPropertyValue(ApiBase::ACCESS_TOKEN);

            case ApiBase::PARAM_FILTER_KEYS["QUERY"]['placeholder']:
                return $this->query;

            case ApiBase::PARAM_FILTER_KEYS["TIMESTAMP"]['placeholder']:
                $date = new DateTime();
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

    public function getRequestConfig(string $parameterName)
    {
        $config = $this->requestConfigs->where('name', $parameterName)->first();
        if (!$config instanceof SrConfig) {
            return null;
        }
        return $config;
    }

    public function getQueryFilterValue($string)
    {
        if (preg_match_all('~\[(.*?)\]~', $string, $output)) {
            foreach ($output[1] as $key => $value) {
                if (array_key_exists($value, $this->queryArray)) {
                    $string = str_replace($output[0][$key], $this->queryArray[$value], $string, $count);
                } else {
                    return false;
                }
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

    public function getProviderPropertyValue(string $propertyName)
    {
        $property = $this->providerProperties->where('name', $propertyName)->first();
        if (!$property instanceof Property) {
            return null;
        }
        if (!$property->providerProperty instanceof ProviderProperty ) {
            return null;
        }
        return $property->providerProperty->value;
    }

    public function setRequestConfigs(Collection $requestConfigs): void
    {
        $this->requestConfigs = $requestConfigs;
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

}
