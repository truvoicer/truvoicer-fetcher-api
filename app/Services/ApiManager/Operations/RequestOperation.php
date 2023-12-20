<?php
namespace App\Services\ApiManager\Operations;

use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Services\ApiManager\Response\Entity\RequestResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class RequestOperation extends BaseOperations
{
    private string $providerName;

    private function initialize(array $query = []) {
        if (count($query) === 0) {
            throw new BadRequestHttpException("Query empty in item query");
        }
        $this->setQuery("");
        $this->setQueryArray($query);
        if (isset($query['query'])) {
            $this->setQuery($query['query']);
        }
    }

    public function multipleQueryOperation(array $query = []) {
        if (!isset($query['query'])) {
            throw new BadRequestHttpException("Query not set in request.");
        }

        $queryItems = explode(",", $query['query']);

        return array_map(function ($item) use ($query) {
            $query["query"] = $item;
            $this->setQueryArray($query);
            $this->setQuery($item);
            $getResponse = $this->getOperationResponse($this->providerName);

            return $this->buildResponseObject($getResponse);
        }, $queryItems);
    }

    public function runOperation(array $query = []) {
        if (array_key_exists("query_type", $query) && $query["query_type"] === "array") {
            return $this->multipleQueryOperation($query);
        }
        $this->initialize($query);
        return $this->buildResponseObject($this->getOperationResponse($this->providerName));
    }

    public function getOperationRequestContent(array $query = []) {
        $this->initialize($query);
        return $this->getRequestContent($this->providerName);
    }

    private function buildResponseObject(ApiResponse $apiResponse)
    {
        $getResponse = new RequestResponse();
        $getResponse->setStatus($apiResponse->getStatus());
        $getResponse->setMessage('Api request sent');
        $getResponse->setPaginationType($apiResponse->pagination_type);
        $getResponse->setContentType($apiResponse->getContentType());
        $getResponse->setRequestService($apiResponse->getRequestService());
        $getResponse->setCategory($apiResponse->getCategory());
        $getResponse->setProvider($apiResponse->getProvider());
        $getResponse->setRequestData($apiResponse->getRequestData());
        $getResponse->setExtraData($apiResponse->getExtraData());
        return $getResponse;
    }
    public function setProviderName(string $providerName)
    {
        $this->providerName = $providerName;
    }

}
