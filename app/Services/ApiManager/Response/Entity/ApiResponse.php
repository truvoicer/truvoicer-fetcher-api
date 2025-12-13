<?php
namespace App\Services\ApiManager\Response\Entity;


class ApiResponse
{
    // use ObjectTrait;


    public string $status;
    public string $requestType;
    public string $responseFormat;

    public string $message;

    public string $contentType;

    public string $provider;

    public string $request;

    public array $serviceRequest;

    public array $service;

    public string $requestCategory;

    public array $requestData;

    public array $requiredResponseKeys;

    public array $extraData = [];

    public ?string $paginationType = null;


    public function getRequestType(): string
    {
        return $this->requestType;
    }

    public function getServiceRequest(): array
    {
        return $this->serviceRequest;
    }

    public function setRequiredResponseKeys(array $requiredResponseKeys): void
    {
        $this->requiredResponseKeys = $requiredResponseKeys;
    }

    public function setServiceRequest(array $serviceRequest): void
    {
        $this->serviceRequest = $serviceRequest;
    }

    public function getService(): array
    {
        return $this->service;
    }

    public function setService(array $service): void
    {
        $this->service = $service;
    }

    public function setRequestType(string $requestType): void
    {
        $this->requestType = $requestType;
    }

    public function setResponseFormat(string $responseFormat): void
    {
        $this->responseFormat = $responseFormat;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
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
    public function setStatus(string $status): void
    {
        $this->status = $status;
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
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }


    /**
     * @return mixed
     */
    public function getRequestCategory()
    {
        return $this->requestCategory;
    }

    /**
     * @param mixed $requestCategory
     */
    public function setRequestCategory(string $requestCategory): void
    {
        $this->requestCategory = $requestCategory;
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
    public function setProvider(string $provider): void
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
    public function setRequestData(array $requestData): void
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
    public function setExtraData(array $extraData): void
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
    public function toArray() {
        return [];
    }
}
