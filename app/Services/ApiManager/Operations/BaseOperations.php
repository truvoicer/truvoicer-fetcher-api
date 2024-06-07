<?php

namespace App\Services\ApiManager\Operations;

use App\Exceptions\OauthResponseException;
use App\Models\Provider;
use App\Models\ProviderRateLimit;
use App\Models\Sr;
use App\Models\SrConfig;
use App\Models\SrRateLimit;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Client\Oauth\Oauth;
use App\Services\ApiManager\Data\DataConstants;
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
        $providerProperties = $this->providerService->getAllProviderProperties($this->provider);
        if (!$providerProperties) {
            throw new BadRequestHttpException("Provider properties not found for operation.");
        }
        $this->dataProcessor->setRequestConfigs($config);
        $this->dataProcessor->setRequestParameters($parameters);
        $this->dataProcessor->setProviderProperties($providerProperties);
        $this->oath->setProvider($this->provider);
        $this->oath->setSr($this->apiService);
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

    private function runPreRequestTasks()
    {
        switch ($this->dataProcessor->getConfigValue(DataConstants::API_AUTH_TYPE)) {
            case DataConstants::OAUTH2:
                $this->initOauth();
                $this->oath->getAccessToken();
                break;
        }
        return true;
    }

    /**
     * @throws OauthResponseException
     */
    private function getRequest()
    {
        $this->runPreRequestTasks();
        $baseUrl = $this->dataProcessor->getProviderPropertyValue(DataConstants::BASE_URL);
        $apiAuthType = $this->dataProcessor->getConfigValue(DataConstants::API_AUTH_TYPE);
        switch ($apiAuthType) {
            case DataConstants::OAUTH2:
                $apiAuthType = $this->dataProcessor->getConfigValue(DataConstants::OAUTH_API_AUTH_TYPE);
                break;
        }

        $endpoint = $this->dataProcessor->getConfigValue(DataConstants::ENDPOINT);
        $method = $this->dataProcessor->getConfigValue(DataConstants::METHOD);
        $headers = $this->dataProcessor->getConfigValue(DataConstants::HEADERS);
        $body = $this->dataProcessor->getConfigValue(DataConstants::BODY);
        $postBody = $this->dataProcessor->getConfigValue(DataConstants::POST_BODY);
        $query = $this->dataProcessor->getConfigValue(DataConstants::QUERY);

        if ($headers) {
            $headers = $this->buildListValues($headers);
        }
        if ($postBody) {
            $postBody = $this->buildListValues($postBody);
        }
        if ($query) {
            $query = $this->buildListValues($query);
        }

        if (is_array($headers)) {
            $this->apiRequest->setHeaders($headers);
        }

        if (is_string($method)) {
            $this->apiRequest->setMethod($method);
        }
        if (is_array($postBody)) {
            $this->apiRequest->setPostBody($postBody);
        }
        if (is_string($baseUrl . $endpoint)) {
            $this->apiRequest->setUrl($baseUrl . $endpoint);
        }
        if (is_array($query)) {
            $this->apiRequest->setQuery($query);
        }
        if (!empty($body) && is_string($body)) {
            $this->apiRequest->setBody($body);
        }

        switch ($apiAuthType) {
            case DataConstants::AUTH_BEARER:
                $this->getAuthBearerAuthentication();
                break;
            case DataConstants::AUTH_BASIC:
                $this->getBasicAuthentication();
                break;
            case DataConstants::ACCESS_TOKEN:
            case DataConstants::AUTH_NONE:
                break;
        }
        $this->eventsService->apiSendRequestEvent($this->apiRequest);
        try {
            return $this->apiClientHandler->sendRequest($this->apiRequest);
        } catch (Exception $exception) {
            throw new BadRequestHttpException($exception->getMessage());
        }
    }

    private function initOauth()
    {
        $tokenRequestHeaders = $this->dataProcessor->getConfigValue(DataConstants::TOKEN_REQUEST_HEADERS);
        $tokenRequestPostBody = $this->dataProcessor->getConfigValue(DataConstants::TOKEN_REQUEST_POST_BODY);
        $tokenRequestBody = $this->dataProcessor->getConfigValue(DataConstants::TOKEN_REQUEST_BODY);
        $tokenRequestQuery = $this->dataProcessor->getConfigValue(DataConstants::TOKEN_REQUEST_QUERY);

        $headers = $body = $query = [];
        if (is_array($tokenRequestHeaders)) {
            $headers = $this->buildListValues($tokenRequestHeaders);
        }
        if (is_array($tokenRequestPostBody)) {
            $body = $this->buildListValues($tokenRequestPostBody);
        }
        if (is_array($tokenRequestQuery)) {
            $query = $this->buildListValues($tokenRequestQuery);
        }

        $this->oath->setTokenRequestHeaders($headers);
        $this->oath->setTokenRequestPostBody($body);
        $this->oath->setTokenRequestQuery($query);

        if (!empty($tokenRequestBody) && is_string($tokenRequestBody)) {
            $this->oath->setTokenRequestBody($tokenRequestBody);
        }

        $tokenRequestAuthType = $this->dataProcessor->getConfigValue(DataConstants::TOKEN_REQUEST_AUTH_TYPE);
        if (empty($tokenRequestAuthType)) {
            throw new BadRequestHttpException("Token request auth type not found.");
        }

        $this->oath->setAuthType($tokenRequestAuthType);

        $tokenRequestUsername = $this->dataProcessor->getConfigValue(DataConstants::TOKEN_REQUEST_USERNAME);
        if (!empty($tokenRequestUsername)) {
            $this->oath->setUsername(trim($this->dataProcessor->filterParameterValue($tokenRequestUsername)));
        }

        $tokenRequestPassword = $this->dataProcessor->getConfigValue(DataConstants::TOKEN_REQUEST_PASSWORD);
        if (!empty($tokenRequestPassword)) {
            $this->oath->setPassword(trim($this->dataProcessor->filterParameterValue($tokenRequestPassword)));
        }

        $tokenRequestToken = $this->dataProcessor->getConfigValue(DataConstants::TOKEN_REQUEST_TOKEN);
        if (!empty($tokenRequestToken)) {
            $this->oath->setToken($tokenRequestToken);
        }

        $tokenRequestMethod = $this->dataProcessor->getConfigValue(DataConstants::TOKEN_REQUEST_METHOD);

        if (!empty($tokenRequestMethod)) {
            $this->oath->setMethod($tokenRequestMethod);
        }

        $tokenRequestUrl = $this->dataProcessor->getConfigValue(DataConstants::OAUTH_TOKEN_URL);
        if (!empty($tokenRequestUrl)) {
            $this->oath->setUrl($tokenRequestUrl);
        }
    }


    private function getBasicAuthentication()
    {
        $username = $this->dataProcessor->getConfigValue(DataConstants::USERNAME);
        $password = $this->dataProcessor->getConfigValue(DataConstants::PASSWORD);

        if ($username === null && $password === null) {
            throw new BadRequestHttpException("Request config username and password are both not set.");
        }
        if ($password === null || $password === "") {
            $this->apiRequest->addBasicAuthentication(
                trim($this->dataProcessor->filterParameterValue($username))
            );
        }

        $this->apiRequest->addBasicAuthentication(
            trim($this->dataProcessor->filterParameterValue($username)),
            trim($this->dataProcessor->filterParameterValue($password))
        );
    }

    private function getAuthBearerAuthentication()
    {
        $apiAuthType = $this->dataProcessor->getConfigValue(DataConstants::API_AUTH_TYPE);
        if ($apiAuthType == DataConstants::OAUTH2) {
            $getAccessToken = $this->oath->getAccessToken();
            if (!$getAccessToken?->access_token) {
                throw new BadRequestHttpException("Error getting access token.");
            }
            $this->apiRequest->addTokenAuthentication(
                $getAccessToken?->access_token
            );
            return;
        }

        $bearerToken = $this->dataProcessor->getSrConfigItem(DataConstants::BEARER_TOKEN);
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

    private function runAmazonRequest()
    {

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
