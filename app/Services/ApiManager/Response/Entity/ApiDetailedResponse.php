<?php
namespace App\Services\ApiManager\Response\Entity;


use App\Services\ApiManager\Client\Entity\ApiRequest;
use Illuminate\Http\Client\Response;

class ApiDetailedResponse extends ApiResponse
{
    public ?ApiRequest $apiRequest = null;
    public ?Response $response = null;

    public Exception $exception;
    public function getException(): Exception
    {
        return $this->exception;
    }

    public function setException(Exception $exception): void
    {
        $this->exception = $exception;
    }
    public function getApiRequest(): ?ApiRequest
    {
        return $this->apiRequest;
    }

    public function setApiRequest(?ApiRequest $apiRequest): void
    {
        $this->apiRequest = $apiRequest;
    }

    public function getResponse(): ?Response
    {
        return $this->response;
    }

    public function setResponse(?Response $response): void
    {
        $this->response = $response;
    }

}
