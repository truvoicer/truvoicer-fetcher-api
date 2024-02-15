<?php
namespace App\Services\ApiManager\Response;

use App\Models\Provider;
use App\Models\Sr;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\Handlers\Json\JsonResponseHandler;
use App\Services\ApiManager\Response\Handlers\Xml\XmlResponseHandler;
use App\Services\BaseService;
use Exception;
use Illuminate\Http\Client\Response;


class ResponseManager extends BaseService
{

    const CONTENT_TYPES = [
        "JSON" => "application/json",
        "XML" => "text/xml",
        "RSS_XML" => "application/rss+xml"
    ];

    private JsonResponseHandler $jsonResponseHandler;
    private XmlResponseHandler $xmlResponseHandler;

    private Sr $serviceRequest;
    private Provider $provider;
    public string $requestType;

    public function __construct(JsonResponseHandler $jsonResponseHandler, XmlResponseHandler $xmlResponseHandler)
    {
        parent::__construct();
        $this->jsonResponseHandler = $jsonResponseHandler;
        $this->xmlResponseHandler = $xmlResponseHandler;
    }

    private function getContentTypesFromHeaders(Response $response) {
        $headers = $response->headers();
        if (isset($headers['Content-Type']) && is_array($headers['Content-Type'])) {
            return $headers['Content-Type'];
        }
        return [];
    }
    public function getRequestContent(Response $response, ApiRequest $apiRequest)
    {
        try {
            $contentType = null;
            switch ($this->getContentType($response)) {
                case self::CONTENT_TYPES['JSON']:
                    $contentType = "json";
                    $content = $response->json();
                    break;
                case self::CONTENT_TYPES['XML']:
                case self::CONTENT_TYPES['RSS_XML']:
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
            return $this->errorResponse($exception->getMessage(), $apiRequest, $response);
        }
    }

    public function  processResponse(Response $response, ApiRequest $apiRequest)
    {
        try {
            $listItems = [];
            $listData = [];
            $contentType = "na";
            switch ($this->getContentType($response)) {
                case self::CONTENT_TYPES['JSON']:
                    $contentType = "json";
                    $this->jsonResponseHandler->setApiService($this->serviceRequest);
                    $this->jsonResponseHandler->setResponseArray($response->json());
                    $this->jsonResponseHandler->setProvider($this->provider);
                    $listItems = $this->jsonResponseHandler->getListItems();
                    $listData = $this->jsonResponseHandler->getListData();
                    break;
                case self::CONTENT_TYPES['XML']:
                case self::CONTENT_TYPES['RSS_XML']:
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
            return $this->errorResponse($exception->getMessage(), $apiRequest, $response);
        }
    }

    private function errorResponse(string $message, ApiRequest $apiRequest, Response $response) {
        $apiResponse = new ApiResponse();
        $apiResponse->setStatus("error");
        $apiResponse->setRequestType($this->requestType);
        $apiResponse->setMessage($message);
        $apiResponse->setRequestService($this->serviceRequest->name);
        if (is_array($this->serviceRequest->pagination_type) && isset($this->serviceRequest->pagination_type['value'])) {
            $apiResponse->setPaginationType($this->serviceRequest->pagination_type['value']);
        }
        $apiResponse->setCategory($this->serviceRequest->category()->first()->name);
        $apiResponse->setProvider($this->provider->name);
        $apiResponse->setApiRequest($apiRequest);
        $apiResponse->setResponse($response);
        return $apiResponse;
    }

    private function successResponse(string $contentType, array $requestData, array $extraData, ApiRequest $apiRequest, Response $response) {
//        dd($requestData);
        $apiResponse = new ApiResponse();
        $apiResponse->setRequestType($this->requestType);
        $apiResponse->setContentType($contentType);
        if (is_array($this->serviceRequest->pagination_type) && isset($this->serviceRequest->pagination_type['value'])) {
            $apiResponse->setPaginationType($this->serviceRequest->pagination_type['value']);
        }
        $apiResponse->setRequestService($this->serviceRequest->name);
        $apiResponse->setCategory($this->serviceRequest->category()->first()->name);
        $apiResponse->setStatus("success");
        $apiResponse->setProvider($this->provider->name);
        $apiResponse->setRequestData($requestData);
        $apiResponse->setExtraData($extraData);
        $apiResponse->setApiRequest($apiRequest);
        $apiResponse->setResponse($response);
        return $apiResponse;
    }

    private function buildArray(array $array)
    {
        $buildArray = [];
        foreach ($array as $item) {
            array_push($buildArray, $item);
        }
        return $buildArray;
    }

    private function getContentType(Response $response)
    {
        $contentTypeArray = [];
        $headers = $response->headers();
        if (isset($headers['Content-Type']) && is_array($headers['Content-Type'])) {
            $contentTypeArray = $headers['Content-Type'];
        }
        $step = 0;
        foreach (self::CONTENT_TYPES as $key => $item) {
            foreach ($contentTypeArray as $contentType) {
                if (str_contains($contentType, $item)) {
                    return $item;
                }
            }
            $step++;
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

    public function setRequestType(string $requestType): void
    {
        $this->requestType = $requestType;
    }

}
