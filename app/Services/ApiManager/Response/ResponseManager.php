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
    public function getRequestContent(Sr $serviceRequest, Provider $provider, Response $response, ApiRequest $apiRequest)
    {
        $this->setProvider($provider);
        $this->setServiceRequest($serviceRequest);
        try {
            $contentType = null;
            switch ($this->getContentType($response)) {
                case self::CONTENT_TYPES['JSON']:
                    $contentType = "json";
                    $content = $response->json();
                    break;
                case self::CONTENT_TYPES['XML']:
                case self::CONTENT_TYPES['RSS_XML']:
                    return $this->processResponse(
                        $serviceRequest,
                        $provider,
                        $response,
                        $apiRequest
                    );
                    break;
            }
            return $this->successResponse(
                $contentType,
                $content,
                [],
                $response,
                $apiRequest
            );
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), $response, $apiRequest);
        }
    }

    public function  processResponse(Sr $serviceRequest, Provider $provider, Response $response, ApiRequest $apiRequest)
    {
        $this->setProvider($provider);
        $this->setServiceRequest($serviceRequest);
        try {
            $contentType = null;
            switch ($this->getContentType($response)) {
                case self::CONTENT_TYPES['JSON']:
                    $contentType = "json";
                    $this->jsonResponseHandler->setApiService($serviceRequest);
                    $this->jsonResponseHandler->setResponseArray($response->json());
                    $this->jsonResponseHandler->setProvider($provider);
                    $listItems = $this->jsonResponseHandler->getListItems();
                    $listData = $this->jsonResponseHandler->getListData();
                    break;
                case self::CONTENT_TYPES['XML']:
                case self::CONTENT_TYPES['RSS_XML']:
                    $contentType = "xml";
                    $this->xmlResponseHandler->setApiService($serviceRequest);
                    $this->xmlResponseHandler->setProvider($provider);
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
                $response,
                $apiRequest
            );
        } catch (Exception $exception) {
            return $this->errorResponse($exception->getMessage(), $response, $apiRequest);
        }
    }

    private function errorResponse($requestData, Response $response, ApiRequest $apiRequest) {
        $apiResponse = new ApiResponse();
        $apiResponse->setStatus("error");
        $apiResponse->setRequestService($this->serviceRequest->name);
        $apiResponse->setPaginationType($this->serviceRequest->pagination_type);
        $apiResponse->setCategory($this->serviceRequest->category()->first()->name);
        $apiResponse->setProvider($this->provider->name);
        $apiResponse->setRequestData($requestData);
        $apiResponse->setApiRequest($apiRequest->toArray());
        return $apiResponse;
    }

    private function successResponse($contentType, $requestData, $extraData, Response $response, ApiRequest $apiRequest) {
//        dd($requestData);
        $apiResponse = new ApiResponse();
        $apiResponse->setContentType($contentType);
        $apiResponse->setPaginationType($this->serviceRequest->pagination_type);
        $apiResponse->setRequestService($this->serviceRequest->name);
        $apiResponse->setCategory($this->serviceRequest->category()->first()->name);
        $apiResponse->setStatus("success");
        $apiResponse->setProvider($this->provider->name);
        $apiResponse->setRequestData($requestData);
        $apiResponse->setExtraData($extraData);
        $apiResponse->setApiRequest($apiRequest->toArray());
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
}
