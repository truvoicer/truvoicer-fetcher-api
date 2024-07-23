<?php

namespace App\Services\ApiManager\Response;

use App\Models\Provider;
use App\Models\Sr;
use App\Repositories\SrRepository;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Data\DataProcessor;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\Handlers\Json\JsonResponseHandler;
use App\Services\ApiManager\Response\Handlers\Xml\XmlResponseHandler;
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

    private JsonResponseHandler $jsonResponseHandler;
    private XmlResponseHandler $xmlResponseHandler;

    private Sr $serviceRequest;
    private Provider $provider;
    public string $responseFormat;

    public function __construct(JsonResponseHandler $jsonResponseHandler, XmlResponseHandler $xmlResponseHandler)
    {
        parent::__construct();
        $this->jsonResponseHandler = $jsonResponseHandler;
        $this->xmlResponseHandler = $xmlResponseHandler;
    }

    private function getContentTypesFromHeaders(Response $response)
    {
        $headers = $response->headers();
        if (isset($headers['Content-Type']) && is_array($headers['Content-Type'])) {
            return $headers['Content-Type'];
        }
        return [];
    }

    public function getRequestContent(Response $response, ApiRequest $apiRequest)
    {
        try {
            $contentType = 'unknown';
            $content = [];
            switch ($this->getContentType($response)) {
                case self::CONTENT_TYPE_JSON:
                    $contentType = "json";
                    $content = $response->json() ?? [];
                    break;
                case self::CONTENT_TYPE_XML:
                    $contentType = "xml";
                    $content = [$response->body()];
                    break;
            }

            return $this->successResponse(
                $contentType,
                $content,
                [],
                $apiRequest, $response
            );
        } catch (Exception $exception) {
            return $this->errorResponse($exception, $apiRequest, $response);
        }
    }

    public function processResponse(Response $response, ApiRequest $apiRequest)
    {
        try {
            $listItems = [];
            $listData = [];
            $contentType = "na";
            switch ($this->getContentType($response)) {
                case self::CONTENT_TYPE_JSON:
                    $contentType = "json";
                    $this->jsonResponseHandler->setApiService($this->serviceRequest);
                    $this->jsonResponseHandler->setResponseArray($response->json());
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
                $apiRequest, $response
            );
        } catch (Exception $exception) {
            return $this->errorResponse($exception, $apiRequest, $response);
        }
    }

    public function setResponseDefaults(ApiResponse $apiResponse)
    {
        $apiResponse->setRequestType($this->responseFormat);
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
    private function errorResponse(Exception $exception, ApiRequest $apiRequest, Response $response)
    {
        $apiResponse = new ApiResponse();
        $apiResponse->setStatus("error");
        $apiResponse->setMessage($exception->getMessage());
        $apiResponse->setApiRequest($apiRequest);
        $apiResponse->setRequestData([
            "error" => $exception->getMessage(),
            'code' => $exception->getCode(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTrace()
        ]);
//        $apiResponse->setResponse($response);
        return $this->setResponseDefaults($apiResponse);
    }

    private function successResponse(?string $contentType, array $requestData, array $extraData, ApiRequest $apiRequest, Response $response)
    {
        $apiResponse = new ApiResponse();
        $apiResponse->setContentType($contentType);
        $apiResponse->setStatus("success");
        $apiResponse->setRequestData($requestData);
        $apiResponse->setExtraData($extraData);
        $apiResponse->setApiRequest($apiRequest);
        $apiResponse->setResponse($response);
        return $this->setResponseDefaults($apiResponse);
    }

    private function buildArray(array $array)
    {
        switch ($this->serviceRequest->type) {
            case SrRepository::SR_TYPE_SINGLE:
            case SrRepository::SR_TYPE_DETAIL:
                return DataProcessor::buildSingleArray($array);
            case SrRepository::SR_TYPE_LIST:
                return DataProcessor::buildListArray($array);
            default:
                return $array;
        }

    }

    private function getContentType(Response $response)
    {
        $contentTypeArray = [];

        if (!empty($this->responseFormat)) {
            return $this->responseFormat;
        }

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

}
