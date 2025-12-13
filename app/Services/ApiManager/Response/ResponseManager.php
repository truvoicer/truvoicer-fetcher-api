<?php

namespace App\Services\ApiManager\Response;

use App\Enums\Api\ApiType;
use App\Enums\Property\PropertyType;
use App\Enums\Sr\SrType;
use App\Exceptions\Api\Response\ApiResponseException;
use App\Models\Provider;
use App\Models\Sr;
use App\Models\User;
use App\Services\Ai\DeepSeek\DeepSeekClient;
use App\Services\Ai\Gemini\GeminiClient;
use App\Services\Ai\Grok\GrokClient;
use App\Services\Ai\OpenAi\OpenAiClient;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\ApiManager\Data\DataProcessor;
use App\Services\ApiManager\Response\Entity\ApiDetailedResponse;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\Handlers\Json\JsonResponseHandler;
use App\Services\ApiManager\Response\Handlers\Xml\XmlResponseHandler;
use App\Services\ApiServices\ServiceRequests\SrConfigService;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\Client\Response;


class ResponseManager extends BaseService
{
    const CONTENT_TYPE_JSON = "json";
    const CONTENT_TYPE_XML = "xml";
    const CONTENT_TYPES = [
        self::CONTENT_TYPE_JSON => ["application/json"],
        self::CONTENT_TYPE_XML => ["text/xml", "application/xml", "application/rss+xml"],
    ];

    private Sr $serviceRequest;
    private Provider $provider;
    public string $responseFormat;
    public string $requestType;
    private ?ApiType $apiType = null;

    public function __construct(
        private readonly JsonResponseHandler $jsonResponseHandler,
        private readonly XmlResponseHandler $xmlResponseHandler
    ) {
        parent::__construct();
    }

    public function getRequestContent(
        Response $response,
        ApiRequest $apiRequest,
        ?bool $detailedResponse = false
    ) {
        try {
            $contentType = 'unknown';
            $content = [];

            switch ($this->requestType) {
                case 'raw':
                    switch ($this->getContentType($response)) {
                        case self::CONTENT_TYPE_JSON:
                            $contentType = "json";
                            $content = $this->getJsonBody($response);
                            break;
                        case self::CONTENT_TYPE_XML:
                            $contentType = "xml";
                            $content = [$response->body()];
                            break;
                    }
                    break;
                case 'json':
                    switch ($this->getContentType($response)) {
                        case self::CONTENT_TYPE_JSON:
                            $contentType = "json";
                            $content = $this->getJsonBody($response);
                            break;
                        case self::CONTENT_TYPE_XML:
                            $contentType = "xml";
                            $content = $this->xmlResponseHandler->convertXmlToArray($response->body());
                            break;
                    }
            }


            return $this->contentSuccessResponse(
                $contentType,
                $content,
                [],
                $apiRequest,
                $response,
                $detailedResponse
            );
        } catch (Exception $exception) {
            throw new ApiResponseException(
                $exception->getMessage()
            );
        }
    }


    /**
     * @throws Exception
     */
    public function getJsonBody(Response $response): array|null
    {
        switch ($this->getApiType()) {
            case ApiType::AI_DEEP_SEEK:
                $deepSeekClient = app(DeepSeekClient::class);
                return $deepSeekClient->formatApiResponse(
                    $response
                );
            case ApiType::AI_GEMINI:
                $geminiClient = app(GeminiClient::class);
                return $geminiClient->formatApiResponse(
                    $response
                );
            case ApiType::AI_OPEN_AI:
                $openAiClient = app(OpenAiClient::class);
                return $openAiClient->formatApiResponse(
                    $response
                );
            case ApiType::AI_GROK:
                $openAiClient = app(GrokClient::class);
                return $openAiClient->formatApiResponse(
                    $response
                );
            default:
                return $response->json();
        }
    }


    public function processResponse(
        Response $response,
        ApiRequest $apiRequest,
        ?bool $detailedResponse = false
    ) {
        try {
            $listItems = [];
            $listData = [];
            $contentType = "na";
            switch ($this->getContentType($response)) {
                case self::CONTENT_TYPE_JSON:
                    if (empty($response->json())) {
                        throw new Exception("Empty JSON response from provider");
                    }
                    $contentType = "json";
                    $this->jsonResponseHandler->setApiService($this->serviceRequest);
                    $this->jsonResponseHandler->setResponseArray(
                        $this->getJsonBody($response)
                    );
                    $this->jsonResponseHandler->setProvider($this->provider);
                    $listItems = $this->jsonResponseHandler->getListItems();
                    $listData = $this->jsonResponseHandler->getListData();
                    break;
                case self::CONTENT_TYPE_XML:
                    $contentType = "xml";
                    $this->xmlResponseHandler->setApiService($this->serviceRequest);
                    $this->xmlResponseHandler->setProvider($this->provider);
                    $this->xmlResponseHandler->setResponseKeysArray();
                    $this->xmlResponseHandler->setResponseArray($response->body());
                    $listItems = $this->xmlResponseHandler->getListItems();
                    $listData = $this->xmlResponseHandler->getListData();
                    break;
            }
            return $this->successResponse(
                $contentType,
                $this->buildArray($listItems),
                $listData,
                $apiRequest,
                $response,
                $detailedResponse
            );
        } catch (Exception $exception) {
            throw new ApiResponseException(
                $exception->getMessage()
            );
        }
    }

    public function setResponseDefaults(ApiResponse $apiResponse)
    {
        $apiResponse->setRequestType($this->requestType);
        $apiResponse->setResponseFormat($this->responseFormat);
        $apiResponse->setServiceRequest([
            'id' => $this->serviceRequest->id,
            'name' => $this->serviceRequest->name,
        ]);
        $apiResponse->setService([
            'id' => $this->serviceRequest->s()->first()?->id,
            'name' => $this->serviceRequest->s()->first()?->name,
        ]);
        if (is_array($this->serviceRequest->pagination_type) && isset($this->serviceRequest->pagination_type['value'])) {
            $apiResponse->setPaginationType($this->serviceRequest->pagination_type['value']);
        }
        $apiResponse->setRequestCategory($this->serviceRequest->category()->first()->name);
        $apiResponse->setProvider($this->provider->name);
        return $apiResponse;
    }

    private function contentSuccessResponse(
        ?string $contentType,
        array $requestData,
        array $extraData,
        ApiRequest $apiRequest,
        Response $response,
        ?bool $detailedResponse = false
    ) {
        if ($detailedResponse) {
            $apiResponse = new ApiDetailedResponse();
            $apiResponse->setApiRequest($apiRequest);
            $apiResponse->setResponse($response);
        } else {
            $apiResponse = new ApiResponse();
        }

        $apiResponse->setContentType($contentType);
        switch ($contentType) {
            case 'json':
                $apiResponse->setRequiredResponseKeys(DataConstants::JSON_SERVICE_RESPONSE_KEYS);
                break;
            case 'xml':
                $apiResponse->setRequiredResponseKeys(DataConstants::XML_SERVICE_RESPONSE_KEYS);
                break;
        }
        $apiResponse->setStatus("success");
        $apiResponse->setRequestData($requestData);
        $apiResponse->setExtraData($extraData);
        return $this->setResponseDefaults($apiResponse);
    }
    private function successResponse(
        ?string $contentType,
        array $requestData,
        array $extraData,
        ApiRequest $apiRequest,
        Response $response,
        ?bool $detailedResponse = false
    ) {

        if ($detailedResponse) {
            $apiResponse = new ApiDetailedResponse();
            $apiResponse->setApiRequest($apiRequest);
            $apiResponse->setResponse($response);
        } else {
            $apiResponse = new ApiResponse();
        }
        $apiResponse->setContentType($contentType);
        $apiResponse->setStatus("success");
        $apiResponse->setRequestData($requestData);
        $apiResponse->setExtraData($extraData);
        return $this->setResponseDefaults($apiResponse);
    }

    private function buildArray(array $array)
    {
        switch ($this->serviceRequest->type) {
            case SrType::SINGLE:
            case SrType::DETAIL:
                return DataProcessor::buildSingleArray($array);
            case SrType::LIST:
                return DataProcessor::buildListArray($array);
            default:
                return $array;
        }
    }

    private function getContentType(Response $response)
    {
        if (!empty($this->responseFormat)) {
            return $this->responseFormat;
        }
        return self::getContentTypeFromResponse($response);
    }

    public static function getSrResponseContentType(Sr $sr, Response $response): bool|string
    {
        $contentType = SrConfigService::getInstance()->getConfigValue($sr, PropertyType::RESPONSE_FORMAT->value);
        if ($contentType) {
            return $contentType;
        }
        return self::getContentTypeFromResponse($response);
    }
    public static function getContentTypeFromResponse(Response $response): bool|string
    {
        $contentTypeArray = [];
        $headers = $response->headers();

        foreach ($headers as $key => $item) {
            if (strtolower($key) === "content-type") {
                $contentTypeArray = $item;
            }
        }
        foreach ($contentTypeArray as $contentType) {
            $contentType = strtolower($contentType);

            foreach (self::CONTENT_TYPES as $key => $item) {
                foreach ($item as $contentTypeMatch) {
                    $contentTypeMatch = strtolower($contentTypeMatch);
                    if (str_contains($contentType, $contentTypeMatch)) {
                        return $key;
                    }
                }
            }
        }
        return false;
    }

    /**
     * @return Sr
     */
    public function getServiceRequest(): Sr
    {
        return $this->serviceRequest;
    }

    /**
     * @param Sr $serviceRequest
     */
    public function setServiceRequest(Sr $serviceRequest): void
    {
        $this->serviceRequest = $serviceRequest;
    }

    /**
     * @return Provider
     */
    public function getProvider(): Provider
    {
        return $this->provider;
    }

    /**
     * @param Provider $provider
     */
    public function setProvider(Provider $provider): void
    {
        $this->provider = $provider;
    }

    public function setResponseFormat(string $responseFormat): void
    {
        $this->responseFormat = $responseFormat;
    }

    public function setRequestType(string $requestType): void
    {
        $this->requestType = $requestType;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        $this->jsonResponseHandler->setUser($user);
        $this->xmlResponseHandler->setUser($user);
        return $this;
    }
    /**
     * @return ApiType
     */
    public function getApiType(): ApiType
    {
        return $this->apiType;
    }

    /**
     * @param ApiType $apiType
     */
    public function setApiType(
        ApiType $apiType
    ): static {
        $this->apiType = $apiType;
        return $this;
    }
}
