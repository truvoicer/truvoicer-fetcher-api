<?php
namespace App\Services\ApiManager\Response\Entity;

class RequestResponse
{
    private $status;

    private $message;

    private $contentType;

    private $provider;

    private $requestService;

    private $category;

    private $requestData;

    private $extraData;

    private ?string $paginationType = null;

    private array $apiRequest;

    /**
     * @return array
     */
    public function getApiRequest(): array
    {
        return $this->apiRequest;
    }

    /**
     * @param array $apiRequest
     */
    public function setApiRequest(array $apiRequest): void
    {
        $this->apiRequest = $apiRequest;
    }


    public function toArray(): array
    {
        return [
            "url" => $this->getUrl(),
            "headers" => $this->getHeaders(),
            "body" => $this->getBody(),
            "query" => $this->getQuery(),
            "method" => $this->getMethod(),
            "auth" => $this->getAuthentication(),
        ];
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @param mixed $message
     */
    public function setMessage($message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * @param mixed $contentType
     */
    public function setContentType($contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * @return mixed
     */
    public function getRequestService()
    {
        return $this->requestService;
    }

    /**
     * @return mixed
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @param mixed $category
     */
    public function setCategory($category): void
    {
        $this->category = $category;
    }

    /**
     * @param mixed $requestService
     */
    public function setRequestService($requestService): void
    {
        $this->requestService = $requestService;
    }

    /**
     * @return mixed
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param mixed $provider
     */
    public function setProvider($provider): void
    {
        $this->provider = $provider;
    }

    /**
     * @return mixed
     */
    public function getRequestData()
    {
        return $this->requestData;
    }

    /**
     * @param mixed $requestData
     */
    public function setRequestData($requestData): void
    {
        $this->requestData = $requestData;
    }

    /**
     * @return mixed
     */
    public function getExtraData()
    {
        return $this->extraData;
    }

    /**
     * @param mixed $extraData
     */
    public function setExtraData($extraData): void
    {
        $this->extraData = $extraData;
    }

    public function getPaginationType(): ?string
    {
        return $this->paginationType;
    }

    public function setPaginationType(?string $paginationType): void
    {
        $this->paginationType = $paginationType;
    }

}
