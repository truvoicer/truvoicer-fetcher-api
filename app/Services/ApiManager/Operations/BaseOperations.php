<?php

namespace App\Services\ApiManager\Operations;

use App\Models\Provider;
use App\Models\ProviderRateLimit;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\SrRateLimit;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Client\Oauth\Oauth;
use App\Services\ApiManager\Data\DataProcessor;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\ResponseManager;
use App\Services\ApiServices\RateLimitService;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\ApiServices\ServiceRequests\SrParametersService;
use App\Services\Category\CategoryService;
use App\Services\Tools\EventsService;
use App\Services\Provider\ProviderService;
use App\Services\ApiServices\ServiceRequests\SrService;
use App\Services\Tools\SerializerService;
use App\Traits\User\UserTrait;
use DateTime;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class BaseOperations extends ApiBase
{
    use UserTrait;


    protected Provider $provider;
    protected string $apiRequestName;
    protected Sr $apiService;
    protected string $query;
    protected array $queryArray;
    protected string $category;
    protected string $timestamp;

    public function __construct(
        private ProviderService     $providerService,
        private Oauth               $oath,
        private ResponseManager     $responseManager,
        private CategoryService     $categoryService,
        private ApiClientHandler    $apiClientHandler,
        private ApiRequest          $apiRequest,
        private EventsService       $eventsService,
        private SrService           $requestService,
        private SrConfigService     $srConfigService,
        private SrParametersService $srParameterService,
        private RateLimitService    $rateLimitService,
        private DataProcessor       $dataProcessor
    )
    {
    }

    public function getOperationResponse(string $requestType, ?string $providerName = null)
    {
        return $this->responseHandler(
            $requestType,
            $this->runRequest($providerName)
        );
    }

    private function responseHandler(string $requestType, $response)
    {
        $this->responseManager->setServiceRequest($this->apiService);
        $this->responseManager->setProvider($this->provider);
        $this->responseManager->setRequestType($requestType);
        $apiResponse = new ApiResponse();
        $apiResponse->setStatus("error");
        $apiResponse = $this->responseManager->setResponseDefaults($apiResponse);
        if (!$response) {
            $apiResponse->setMessage('Too many requests, please try again later.');
            return $apiResponse;
        }
        switch ($requestType) {
            case "json":
                return $this->responseManager->processResponse($response, $this->apiRequest);
            case "raw":
                return $this->responseManager->getRequestContent($response, $this->apiRequest);
            default:
                $apiResponse->setMessage('Invalid request type.');
                return $apiResponse;
        }
    }

    private function requestHandler()
    {
        $srRateLimit = $this->rateLimitService->findParentOrChildRateLimitBySr($this->apiService);
        if (!$srRateLimit) {
            return $this->getRequest();
        }
        $providerRateLimit = $this->provider->providerRateLimit()->first();
        $rateLimiterKey = null;
        $maxAttempts = null;
        $decaySeconds = null;
        $delay_seconds_per_request = null;
        if ($srRateLimit instanceof SrRateLimit && $srRateLimit->override) {
            $rateLimiterKey = sprintf(
                "%s_%s_%s_%s",
                $this->provider->id,
                $this->provider->name,
                $this->apiService->id,
                $this->apiService->name
            );
            $maxAttempts = $srRateLimit->max_attempts;
            $decaySeconds = $srRateLimit->decay_seconds;
            $delay_seconds_per_request = $srRateLimit->delay_seconds_per_request;
        } else if ($providerRateLimit instanceof ProviderRateLimit) {
            $rateLimiterKey = sprintf(
                "%s_%s",
                $this->provider->id,
                $this->provider->name
            );
            $maxAttempts = $providerRateLimit->max_attempts;
            $decaySeconds = $providerRateLimit->decay_seconds;
            $delay_seconds_per_request = $providerRateLimit->delay_seconds_per_request;
        }

        return RateLimiter::attempt(
            $rateLimiterKey,
            $maxAttempts,
            function () {
                return $this->getRequest();
            },
            $decaySeconds,
        );
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

        $config = $this->srConfigService->findConfigForOperationBySr($this->apiService);
        if (!$config) {
            throw new BadRequestHttpException("Request config not found for operation.");
        }
        $parameters = $this->srParameterService->findParametersForOperationBySr($this->apiService);
        if (!$parameters) {
            throw new BadRequestHttpException("Request parameters not found for operation.");
        }
        $providerProperties = $this->providerService->getProviderProperties($this->provider);
        if (!$providerProperties) {
            throw new BadRequestHttpException("Provider properties not found for operation.");
        }
        $this->dataProcessor->setRequestConfigs($config);
        $this->dataProcessor->setRequestParameters($parameters);
        $this->dataProcessor->setProviderProperties($providerProperties);
        $this->oath->setProvider($this->provider);
        $getRequest = $this->requestHandler();
        return $getRequest;
    }

    private function buildListValues(array $listValues)
    {
        return array_combine(
            array_column($listValues, 'name'),
            array_map(
                fn($value) => $this->dataProcessor->filterParameterValue($value),
                array_column($listValues, 'value')
            )
        );
    }

    private function getRequest()
    {
        $baseUrl = $this->dataProcessor->getProviderPropertyValue(self::BASE_URL);
        $accessTokenValue = $this->dataProcessor->getProviderPropertyValue(self::ACCESS_TOKEN);
        switch ($this->dataProcessor->getProviderPropertyValue(self::API_AUTH_TYPE)) {
            case parent::OAUTH2:
                $tokenRequestHeaders = $this->dataProcessor->getRequestConfig('token_request_headers');
                $tokenRequestBody = $this->dataProcessor->getRequestConfig('token_request_body');
                $tokenRequestQuery = $this->dataProcessor->getRequestConfig('token_request_query');

                $headers = $body = $query = [];
                if ($tokenRequestHeaders) {
                    $headers = $this->buildListValues($tokenRequestHeaders->array_value);
                }
                if ($tokenRequestBody) {
                    $body = $this->buildListValues($tokenRequestBody->array_value);
                }
                if ($tokenRequestQuery) {
                    $query = $this->buildListValues($tokenRequestQuery->array_value);
                }

                $this->oath->setTokenRequestHeaders($headers);
                $this->oath->setTokenRequestBody($body);
                $this->oath->setTokenRequestQuery($query);

                $tokenRequestAuthType = $this->dataProcessor->getRequestConfig(self::TOKEN_REQUEST_AUTH_TYPE);
                if (!$tokenRequestAuthType instanceof SrConfig) {
                    throw new BadRequestHttpException("Token request auth type not found.");
                }

                $this->oath->setAuthType($tokenRequestAuthType->value);

                $tokenRequestUsername = $this->dataProcessor->getRequestConfig(self::TOKEN_REQUEST_USERNAME);
                if ($tokenRequestUsername instanceof SrConfig) {
                    $this->oath->setUsername($tokenRequestUsername->value);
                }

                $tokenRequestPassword = $this->dataProcessor->getRequestConfig(self::TOKEN_REQUEST_PASSWORD);
                if ($tokenRequestPassword instanceof SrConfig) {
                    $this->oath->setPassword($tokenRequestPassword->value);
                }

                $tokenRequestToken = $this->dataProcessor->getRequestConfig(self::TOKEN_REQUEST_TOKEN);
                if ($tokenRequestToken instanceof SrConfig) {
                    $this->oath->setToken($tokenRequestToken->value);
                }

                $tokenRequestMethod = $this->dataProcessor->getRequestConfig(self::TOKEN_REQUEST_METHOD);
                if ($tokenRequestMethod instanceof SrConfig) {
                    $this->oath->setMethod($tokenRequestMethod->value);
                }

                $tokenRequestUrl = $this->dataProcessor->getProviderPropertyValue(self::OAUTH_TOKEN_URL);
                if (!empty($tokenRequestUrl)) {
                    $this->oath->setUrl($tokenRequestUrl);
                }

                $accessToken = $this->oath->getAccessToken();
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders([
                    "Authorization" => "Bearer " . $accessToken->getAccessToken(),
                    "Client-ID" => $accessTokenValue
                ]);
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($baseUrl . $endpoint);
                $this->setRequestData();
                break;
//            case "amazon-sdk":
//                return $this->runAmazonRequest();
            case parent::AUTH_BEARER:
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders($this->getHeaders());
                $this->getAuthBearerAuthentication();
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($baseUrl . $endpoint);
                $this->setRequestData();
                break;
            case parent::AUTH_BASIC:
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders($this->getHeaders());
                $this->getBasicAuthentication();
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($baseUrl . $endpoint);
                $this->setRequestData();
                break;
            case parent::ACCESS_TOKEN:
            case parent::AUTH_NONE:
                $endpoint = $this->getEndpoint();
                $this->apiRequest->setHeaders($this->getHeaders());
                $this->apiRequest->setMethod($this->getMethod());
                $this->apiRequest->setUrl($baseUrl . $endpoint);
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

    private function getBasicAuthentication()
    {
        $usernameConfig = $this->dataProcessor->getRequestConfig("username");
        $passwordConfig = $this->dataProcessor->getRequestConfig("password");
        $username = null;
        $password = null;
        if ($usernameConfig instanceof SrConfig) {
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
                $this->dataProcessor->filterParameterValue($username)
            );
        }
        $this->apiRequest->addBasicAuthentication(
            $this->dataProcessor->filterParameterValue($username),
            $password
        );
    }

    private function getAuthBearerAuthentication()
    {
        $bearerToken = $this->dataProcessor->getRequestConfig("bearer_token");
        if (!$bearerToken instanceof SrConfig) {
            throw new BadRequestHttpException("Request config bearer token not found.");
        }
        if (empty($bearerToken->value)) {
            throw new BadRequestHttpException("Request config bearer token is invalid.");
        }
        $this->apiRequest->addTokenAuthentication(
            $this->dataProcessor->filterParameterValue($bearerToken->value)
        );
    }

    private function setRequestData()
    {
        switch ($this->dataProcessor->getProviderPropertyValue(self::API_TYPE)) {
            case "query_string":
                $requestQueryArray = $this->dataProcessor->buildRequestQuery();
                $this->apiRequest->setQuery($requestQueryArray);
                break;
            case "query_schema":
                $this->apiRequest->setBody($this->dataProcessor->getRequestBody());
                break;
            default:
                throw new BadRequestHttpException(
                    sprintf("Provider property (api_type) not set or not valid for %s",
                        $this->provider->label)
                );
        }
    }


    private function runAmazonRequest()
    {

    }

    private function getHeaders()
    {
        $headers = ["Content-Type" => "application/json;charset=utf-8"];
        $getHeaders = $this->dataProcessor->getRequestConfig("headers");
        if (!$getHeaders instanceof SrConfig) {
            return $headers;
        }
        if (empty($getHeaders->array_value)) {
            return $headers;
        }
        $headerArray = $getHeaders->array_value;
        foreach ($headerArray as $item) {
            $headers[$item["name"]] = $this->dataProcessor->filterParameterValue($item["value"]);
        }
        return $headers;
    }

    private function getEndpoint()
    {
        $endpoint = $this->dataProcessor->getRequestConfig("endpoint");
        if (!$endpoint instanceof SrConfig) {
            throw new BadRequestHttpException("Endpoint is not specified in request config");
        }
        if (empty($endpoint->value)) {
            throw new BadRequestHttpException("Endpoint is not valid");
        }
        return $this->dataProcessor->getQueryFilterValue($endpoint->value);
    }

    private function getMethod()
    {
        $method = $this->dataProcessor->getRequestConfig("request_method");
        if (!$method instanceof SrConfig) {
            throw new BadRequestHttpException("Request method is not specified in request config");
        }
        if (empty($method->value)) {
            throw new BadRequestHttpException("Request method is invalid");
        }
        return $this->dataProcessor->getQueryFilterValue($method->value);
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
        $this->dataProcessor->setQuery($query);
    }

    public function setTimestamp(string $timestamp)
    {
        $this->timestamp = $timestamp;
    }


    /**
     * @param mixed $queryArray
     */
    public function setQueryArray($queryArray): void
    {
        $this->queryArray = $queryArray;
        $this->dataProcessor->setQueryArray($queryArray);
    }


}
