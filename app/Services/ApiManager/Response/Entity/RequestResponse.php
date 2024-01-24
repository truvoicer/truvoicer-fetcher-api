<?php
namespace App\Services\ApiManager\Response\Entity;

use App\Traits\ObjectTrait;

class RequestResponse
{
    use ObjectTrait;
    private string $status;

    private string $message;

    private string $contentType;

    private string $provider;

    private string $requestService;

    private string $category;

    private array $requestData;

    private array $extraData;

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

    /**
     * @return string
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
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * @param string $contentType
     */
    public function setContentType(string $contentType): void
    {
        $this->contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getRequestService(): string
    {
        return $this->requestService;
    }

    /**
     * @return string
     */
    public function getCategory(): string
    {
        return $this->category;
    }

    /**
     * @param string $category
     */
    public function setCategory(string$category): void
    {
        $this->category = $category;
    }

    /**
     * @param string $requestService
     */
    public function setRequestService(string $requestService): void
    {
        $this->requestService = $requestService;
    }

    /**
     * @return string
     */
    public function getProvider(): string
    {
        return $this->provider;
    }

    /**
     * @param string $provider
     */
    public function setProvider(string $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * @param array $requestData
     */
    public function setRequestData(array $requestData): void
    {
        $this->requestData = $requestData;
    }

    /**
     * @return array
     */
    public function getExtraData(): array
    {
        return $this->extraData;
    }

    /**
     * @param array $extraData
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

}
