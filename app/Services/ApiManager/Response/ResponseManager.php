<?php
namespace App\Services\ApiManager\Response;

use App\Models\Provider;
use App\Models\ServiceRequest;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\Handlers\Json\JsonResponseHandler;
use App\Services\ApiManager\Response\Handlers\Xml\XmlResponseHandler;
use App\Services\BaseService;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class ResponseManager extends BaseService
{

    const CONTENT_TYPES = [
        "JSON" => "application/json",
        "XML" => "text/xml",
        "RSS_XML" => "application/rss+xml"
    ];

    private JsonResponseHandler $jsonResponseHandler;
    private XmlResponseHandler $xmlResponseHandler;

    private ServiceRequest $serviceRequest;
    private Provider $provider;

    public function __construct(JsonResponseHandler $jsonResponseHandler, XmlResponseHandler $xmlResponseHandler,
                                TokenStorageInterface $tokenStorage)
    {
        parent::__construct($tokenStorage);
        $this->jsonResponseHandler = $jsonResponseHandler;
        $this->xmlResponseHandler = $xmlResponseHandler;
    }

    public function getRequestContent(ServiceRequest $serviceRequest, Provider $provider, ResponseInterface $response, ApiRequest $apiRequest)
    {
        $this->setProvider($provider);
        $this->setServiceRequest($serviceRequest);
        try {
            $contentType = null;
            switch ($this->getContentType($response->getHeaders()['content-type'])) {
                case self::CONTENT_TYPES['JSON']:
                    $contentType = "json";
                    $content = $response->toArray();
                    break;
                case self::CONTENT_TYPES['XML']:
                case self::CONTENT_TYPES['RSS_XML']:
                    $contentType = "xml";
                    $content = $response->getContent();
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

    public function  processResponse(ServiceRequest $serviceRequest, Provider $provider, ResponseInterface $response, ApiRequest $apiRequest)
    {
        $this->setProvider($provider);
        $this->setServiceRequest($serviceRequest);
        try {
            $contentType = null;
            switch ($this->getContentType($response->getHeaders()['content-type'])) {
                case self::CONTENT_TYPES['JSON']:
                    $contentType = "json";
                    $this->jsonResponseHandler->setApiService($serviceRequest);
                    $this->jsonResponseHandler->setResponseArray($response->toArray());
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
                    $this->xmlResponseHandler->setResponseArray($response->getContent());
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

    private function errorResponse($requestData, ResponseInterface $response, ApiRequest $apiRequest) {
        $apiResponse = new ApiResponse();
        $apiResponse->setStatus("error");
        $apiResponse->setRequestService($this->serviceRequest->getServiceRequestName());
        $apiResponse->setPaginationType($this->serviceRequest->getPaginationType());
        $apiResponse->setCategory($this->serviceRequest->getCategory()->getCategoryName());
        $apiResponse->setProvider($this->provider->getProviderName());
        $apiResponse->setRequestData($requestData);
        $apiResponse->setApiRequest($apiRequest->toArray());
        return $apiResponse;
    }

    private function successResponse($contentType, $requestData, $extraData, ResponseInterface $response, ApiRequest $apiRequest) {
        $apiResponse = new ApiResponse();
        $apiResponse->setContentType($contentType);
        $apiResponse->setPaginationType($this->serviceRequest->getPaginationType());
        $apiResponse->setRequestService($this->serviceRequest->getServiceRequestName());
        $apiResponse->setCategory($this->serviceRequest->getCategory()->getCategoryName());
        $apiResponse->setStatus("success");
        $apiResponse->setProvider($this->provider->getProviderName());
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

    private function getContentType(array $contentTypeArray = [])
    {
        foreach (self::CONTENT_TYPES as $key => $item) {
            if (strpos($contentTypeArray[0], $item) !== false) {
                return $item;
            }
        }
        return false;
    }

    /**
     * @return ServiceRequest
     */
    public function getServiceRequest(): ServiceRequest
    {
        return $this->serviceRequest;
    }

    /**
     * @param ServiceRequest $serviceRequest
     */
    public function setServiceRequest(ServiceRequest $serviceRequest): void
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
