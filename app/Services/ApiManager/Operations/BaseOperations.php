<?php

namespace App\Services\ApiManager\Operations;

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Client\Oauth\Oauth;
use App\Services\ApiManager\Response\ResponseManager;
use App\Services\Category\CategoryService;
use App\Services\Tools\EventsService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\RequestService;
use App\Services\Tools\SerializerService;
use DateTime;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BaseOperations extends ApiBase
{
    private Oauth $oath;
    private ProviderService $providerService;
    private SerializerService $serializerService;
    private EventsService $eventsService;
    private RequestService $requestService;
    protected Provider $provider;
    protected string $apiRequestName;
    protected ServiceRequest $apiService;
    protected CategoryService $categoryService;
    protected ResponseManager $responseManager;
    protected ApiClientHandler $apiClientHandler;
    protected ApiRequest $apiRequest;
    protected string $query;
    protected array $queryArray;
    protected string $category;
    protected string $timestamp;

    public function __construct(ProviderService $providerService, SerializerService $serializerService, Oauth $oauth,
                                ResponseManager $responseManager, CategoryService $categoryService, ApiClientHandler $apiClientHandler,
                                ApiRequest $apiRequest, EventsService $eventsService, RequestService $requestService)
    {
        $this->providerService = $providerService;
        $this->serializerService = $serializerService;
        $this->oath = $oauth;
        $this->responseManager = $responseManager;
        $this->apiRequest = $apiRequest;
        $this->categoryService = $categoryService;
        $this->eventsService = $eventsService;
        $this->apiClientHandler = $apiClientHandler;
        $this->requestService = $requestService;
    }

    public function getRequestContent(string $providerName = null)
    {
        $response = $this->runRequest($providerName);
        return $this->responseManager->getRequestContent($this->apiService, $this->provider, $response, $this->apiRequest);
    }

    public function getOperationResponse(string $providerName = null)
    {
        $response = $this->runRequest($providerName);
        return $this->responseManager->processResponse($this->apiService, $this->provider, $response, $this->apiRequest);
    }

    public function runRequest(string $providerName = null)
    {
        $this->setProvider($providerName);
        $this->setApiService();
        return $this->getRequest();
    }

    private function getRequest()
    {
        switch ($this->providerService->getProviderPropertyValue($this->provider, self::API_AUTH_TYPE)) {
            case parent::OAUTH:
            case parent::OAUTH_BODY:
            case parent::OAUTH_BASIC:
            case parent::OAUTH_BEARER:
                $this->oath->setProvider($this->provider);
                $accessToken = $this->oath->getAccessToken();
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders([
                    "Authorization" => "Bearer " . $accessToken->getAccessToken(),
                    "Client-ID" => $this->provider->getProviderAccessKey()
                ]);
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($this->provider->getProviderApiBaseUrl() . $endpoint);
                $this->setRequestData();
                break;
//            case "amazon-sdk":
//                return $this->runAmazonRequest();
            case parent::AUTH_BEARER:
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders($this->getHeaders());
                $this->getAuthBearerAuthentication();
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($this->provider->getProviderApiBaseUrl() . $endpoint);
                $this->setRequestData();
                break;
            case parent::AUTH_BASIC:
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders($this->getHeaders());
                $this->getBasicAuthentication();
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($this->provider->getProviderApiBaseUrl() . $endpoint);
                $this->setRequestData();
                break;
            case parent::ACCESS_TOKEN:
            case parent::AUTH_NONE:
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders($this->getHeaders());
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($this->provider->getProviderApiBaseUrl() . $endpoint);
                $this->setRequestData();
                break;
        }
        $this->eventsService->apiSendRequestEvent($this->apiRequest);
        try {
            return $this->apiClientHandler->sendRequest($this->apiRequest);
        } catch (Exception $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    private function getBasicAuthentication() {
        $username = $this->getRequestConfig("username");
        $password = $this->getRequestConfig("password");
        if ($username === null && $password === null) {
            throw new BadRequestHttpException("Request config username and password are both not set.");
        }
        if ($password === null || $password === "") {
            $this->apiRequest->addBasicAuthentication(
                $this->filterParameterValue($username->getItemValue())
            );
        }
        $this->apiRequest->addBasicAuthentication(
            $this->filterParameterValue($username->getItemValue()),
            $password->getItemValue()
        );
    }
    private function getAuthBearerAuthentication() {
        $bearerToken = $this->getRequestConfig("bearer_token");
        if (!$bearerToken) {
            throw new BadRequestHttpException("Request config bearer token not set.");
        }
        $this->apiRequest->addTokenAuthentication(
            $this->filterParameterValue($bearerToken->getItemValue())
        );
    }

    private function setRequestData() {
        $providerServiceParams = $this->requestService->getRequestParametersByRequestName(
            $this->provider,
            $this->apiRequestName);
        switch ($this->providerService->getProviderPropertyValue($this->provider, self::API_TYPE)) {
            case "query_string":
                $requestQueryArray = $this->buildRequestQuery($providerServiceParams);
                $this->apiRequest->setQuery($requestQueryArray);
                break;
            case "query_schema":
                $this->apiRequest->setBody($this->getRequestBody($providerServiceParams));
                break;
            default:
                throw new BadRequestHttpException(
                    sprintf("Provider property (api_type) not set or not valid for %s",
                        $this->provider->getProviderLabel())
                );
        }
    }

    private function getRequestBody($providerServiceParams) {
        $queryArray = [];
        foreach ($providerServiceParams as $requestParameter) {
            array_push($queryArray, $this->filterParameterValue($requestParameter->getParameterValue()));
        }
        return implode(" ", $queryArray);
    }

    private function runAmazonRequest() {
//        $providerServiceParams = $this->requestService->getRequestParametersByRequestName(
//            $this->provider,
//            $this->apiRequestName);
//        $requestQueryArray = $this->buildRequestQuery($providerServiceParams);
//        $service = $this->amazonApiManager->getApiRequest($this->apiService);
//        $service->setAccessKey($this->provider->getProviderAccessKey());
//        $service->setSecretKey($this->provider->getProviderSecretKey());
//        $service->setRegion("eu-west-1");
//        $service->setHost("webservices.amazon.co.uk");
//        $service->setPartnerTag($this->provider->getProviderUserId());
//        return $service->searchItems($this->query, $requestQueryArray['limit']);
    }

    private function getHeaders()
    {
        $headers = ["Content-Type" => "application/json;charset=utf-8"];
        $getHeaders = $this->getRequestConfig("headers");
        if ($getHeaders === null) {
            return $headers;
        }
        $headerArray = $getHeaders->getItemArrayValue();
        foreach ($headerArray as $item) {
            $headers[$item["name"]] = $this->filterParameterValue($item["value"]);
        }
        return $headers;
    }

    private function getEndpoint()
    {
        $endpoint = $this->getRequestConfig("endpoint");
        if ($endpoint === null) {
            throw new BadRequestHttpException("Endpoint is not specified in request config");
        }
        return $this->getQueryFilterValue($endpoint->getItemValue());
    }

    private function getMethod()
    {
        $method = $this->getRequestConfig("request_method");
        if ($method === null) {
            throw new BadRequestHttpException("Request method is not specified in request config");
        }
        return $this->getQueryFilterValue($method->getItemValue());
    }

    private function getQueryFilterValue($string)
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

    private function getRequestConfig(string $parameterName)
    {
        return $this->requestService->getRequestConfigByName($this->provider, $this->apiService, $parameterName);
    }

    public function buildRequestQuery(array $apiParamsArray)
    {
        $queryArray = [];
        foreach ($apiParamsArray as $requestParameter) {
            $paramValue = $this->filterParameterValue($requestParameter->getParameterValue());
            if (empty($paramValue)) {
                continue;
            }
            $value = trim($paramValue);
            if (!array_key_exists($requestParameter->getParameterName(), $queryArray)) {
                $queryArray[$requestParameter->getParameterName()] = $value;
                continue;
            }
            if (empty($queryArray[$requestParameter->getParameterName()])) {
                $queryArray[$requestParameter->getParameterName()] = $value;
                continue;
            }
            $queryArray[$requestParameter->getParameterName()] = $queryArray[$requestParameter->getParameterName()] . "," . $value;

        }
        return $queryArray;
    }

    private function filterParameterValue($paramValue)
    {
        if (preg_match_all('~\[(.*?)\]~', $paramValue, $output)) {
            foreach ($output[1] as $key => $value) {
                $filterReservedParam = $this->getReservedParamsValues($output[0][$key]);
                $paramValue = str_replace($output[0][$key], $filterReservedParam, $paramValue, $count);
            }
        }
        return $paramValue;
    }

    private function getReservedParamsValues($paramValue)
    {
        foreach (self::PARAM_FILTER_KEYS as $key => $value) {
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
            case self::PARAM_FILTER_KEYS["PROVIDER_USER_ID"]['placeholder']:
                return $this->provider->getProviderUserId();

            case self::PARAM_FILTER_KEYS["SECRET_KEY"]['placeholder']:
                return $this->provider->getProviderSecretKey();

            case self::PARAM_FILTER_KEYS["ACCESS_KEY"]['placeholder']:
                return $this->provider->getProviderAccessKey();

            case self::PARAM_FILTER_KEYS["QUERY"]['placeholder']:
                return $this->query;

            case self::PARAM_FILTER_KEYS["TIMESTAMP"]['placeholder']:
                $date = new DateTime();
                return $date->format("Y-m-d h:i:s");
        }
        return $this->formatValue($this->getQueryFilterValue($paramValue));
    }

    private function formatValue($value)
    {
        if (is_numeric($value)) {
            return (int)$value;
        }
        return $value;
    }

    public function setApiRequestName(string $apiRequestName)
    {
        $this->apiRequestName = $apiRequestName;
    }

    public function setApiService()
    {
        $apiService = $this->requestService->getRequestByName($this->provider, $this->apiRequestName);
        if ($apiService === null) {
            throw new BadRequestHttpException("Service request doesn't exist, check config.");
        }
        $this->apiService = $apiService;
    }

    /**
     * @param $providerName
     */
    public function setProvider($providerName): void
    {
        $provider = $this->providerService->getUserProviderByName($providerName);
        if ($provider === null) {
            throw new BadRequestHttpException("Provider in request not found...");
        } else {
            $this->provider = $provider->getProvider();
        }
    }

    public function setQuery(string $query)
    {
        $this->query = $query;
    }

    public function setTimestamp(string $timestamp)
    {
        $this->timestamp = $timestamp;
    }

    public function setCategory(string $category)
    {
        $this->category = $category;
    }

    /**
     * @return mixed
     */
    public function getQueryArray()
    {
        return $this->queryArray;
    }

    /**
     * @param mixed $queryArray
     */
    public function setQueryArray($queryArray): void
    {
        $this->queryArray = $queryArray;
    }


}
