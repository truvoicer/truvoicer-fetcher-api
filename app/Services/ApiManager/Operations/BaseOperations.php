<?php

namespace App\Services\ApiManager\Operations;

use App\Models\Provider;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Client\Oauth\Oauth;
use App\Services\ApiManager\Response\ResponseManager;
use App\Services\Category\CategoryService;
use App\Services\Tools\EventsService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Tools\SerializerService;
use App\Traits\User\UserTrait;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BaseOperations extends ApiBase
{
    use UserTrait;
    private Oauth $oath;
    private ProviderService $providerService;
    private SerializerService $serializerService;
    private EventsService $eventsService;
    private SrService $requestService;
    protected Provider $provider;
    protected string $apiRequestName;
    protected Sr $apiService;
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
                                ApiRequest      $apiRequest, EventsService $eventsService, SrService $requestService)
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

    public function getRequestContent(?string $providerName = null)
    {
        $response = $this->runRequest($providerName);
        return $this->responseManager->getRequestContent($this->apiService, $this->provider, $response, $this->apiRequest);
    }

    public function getOperationResponse(?string $providerName = null)
    {
        $response = $this->runRequest($providerName);
        return $this->responseManager->processResponse($this->apiService, $this->provider, $response, $this->apiRequest);
    }

    public function runRequest(?string $providerName = null)
    {
        if (!isset($this->provider) && !empty($providerName)) {
            $this->setProviderByName($providerName);
        }
        if (
            !isset($this->apiService) &&
            !empty($this->provider) &&
            !empty($this->apiRequestName)
        ) {
            $this->setApiService();
        }
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
                    "Client-ID" => $this->provider->access_key
                ]);
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($this->provider->api_base_url . $endpoint);
                $this->setRequestData();
                break;
//            case "amazon-sdk":
//                return $this->runAmazonRequest();
            case parent::AUTH_BEARER:
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders($this->getHeaders());
                $this->getAuthBearerAuthentication();
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($this->provider->api_base_url . $endpoint);
                $this->setRequestData();
                break;
            case parent::AUTH_BASIC:
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders($this->getHeaders());
                $this->getBasicAuthentication();
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($this->provider->api_base_url . $endpoint);
                $this->setRequestData();
                break;
            case parent::ACCESS_TOKEN:
            case parent::AUTH_NONE:
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders($this->getHeaders());
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($this->provider->api_base_url . $endpoint);
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
        $usernameConfig = $this->getRequestConfig("username");
        $passwordConfig = $this->getRequestConfig("password");
        $username = null;
        $password = null;
        if ($usernameConfig  instanceof SrConfig) {
            $username = $usernameConfig->value;
        }
        if ($passwordConfig instanceof SrConfig) {
            $password = $passwordConfig->value;
        }

        if ($username === null && $password === null) {
            throw new BadRequestHttpException("Request config username and password are both not set.");
        }
        if ($password === null || $password === "") {
            $this->apiRequest->addBasicAuthentication(
                $this->filterParameterValue($username)
            );
        }
        $this->apiRequest->addBasicAuthentication(
            $this->filterParameterValue($username),
            $password
        );
    }
    private function getAuthBearerAuthentication() {
        $bearerToken = $this->getRequestConfig("bearer_token");
        if (!$bearerToken instanceof SrConfig) {
            throw new BadRequestHttpException("Request config bearer token not found.");
        }
        if (empty($bearerToken->value)) {
            throw new BadRequestHttpException("Request config bearer token is invalid.");
        }
        $this->apiRequest->addTokenAuthentication(
            $this->filterParameterValue($bearerToken->value)
        );
    }

    private function setRequestData() {
        $providerServiceParams = $this->requestService->getRequestParametersByRequestName(
            $this->provider,
            $this->apiRequestName
        );
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
                        $this->provider->label)
                );
        }
    }

    private function getRequestBody($providerServiceParams) {
        $queryArray = [];
        foreach ($providerServiceParams as $requestParameter) {
            array_push($queryArray, $this->filterParameterValue($requestParameter->value));
        }
        return implode(" ", $queryArray);
    }

    private function runAmazonRequest() {
//        $providerServiceParams = $this->requestService->getRequestParametersByRequestName(
//            $this->provider,
//            $this->apiRequestName);
//        $requestQueryArray = $this->buildRequestQuery($providerServiceParams);
//        $service = $this->amazonApiManager->getApiRequest($this->apiService);
//        $service->setAccessKey($this->provider->access_key);
//        $service->setSecretKey($this->provider->secret_key);
//        $service->setRegion("eu-west-1");
//        $service->setHost("webservices.amazon.co.uk");
//        $service->setPartnerTag($this->provider->user_id);
//        return $service->searchItems($this->query, $requestQueryArray['limit']);
    }

    private function getHeaders()
    {
        $headers = ["Content-Type" => "application/json;charset=utf-8"];
        $getHeaders = $this->getRequestConfig("headers");
        if (!$getHeaders instanceof SrConfig) {
            return $headers;
        }
        if (empty($getHeaders->array_value)) {
            return $headers;
        }
        $headerArray = $getHeaders->array_value;
        foreach ($headerArray as $item) {
            $headers[$item["name"]] = $this->filterParameterValue($item["value"]);
        }
        return $headers;
    }

    private function getEndpoint()
    {
        $endpoint = $this->getRequestConfig("endpoint");
        if (!$endpoint instanceof SrConfig) {
            throw new BadRequestHttpException("Endpoint is not specified in request config");
        }
        if (empty($endpoint->value)) {
            throw new BadRequestHttpException("Endpoint is not valid");
        }
        return $this->getQueryFilterValue($endpoint->value);
    }

    private function getMethod()
    {
        $method = $this->getRequestConfig("request_method");
        if (!$method instanceof SrConfig) {
            throw new BadRequestHttpException("Request method is not specified in request config");
        }
        if (empty($method->value)) {
            throw new BadRequestHttpException("Request method is invalid");
        }
        return $this->getQueryFilterValue($method->value);
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
        $config = $this->requestService->getRequestConfigByName($this->provider, $this->apiService, $parameterName);
        if (!$config instanceof  SrConfig) {
            return null;
        }
        return $config;
    }

    public function buildRequestQuery(Collection $apiParamsArray)
    {
        $queryArray = [];
        foreach ($apiParamsArray as $requestParameter) {
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
                return $this->provider->user_id;

            case self::PARAM_FILTER_KEYS["SECRET_KEY"]['placeholder']:
                return $this->provider->secret_key;

            case self::PARAM_FILTER_KEYS["ACCESS_KEY"]['placeholder']:
                return $this->provider->access_key;

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

    public function setSr(Sr $sr)
    {
        $this->apiService = $sr;
        $this->apiRequestName = $sr->name;
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
    public function setProviderByName($providerName): void
    {
        $provider = $this->providerService->getUserProviderByName($this->getUser(), $providerName);

        if (!$provider instanceof Provider) {
            throw new BadRequestHttpException("Provider in request not found...");
        } else {
            $this->provider = $provider;
        }
    }

    public function setProvider(Provider $provider): void
    {
        $this->provider = $provider;
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
