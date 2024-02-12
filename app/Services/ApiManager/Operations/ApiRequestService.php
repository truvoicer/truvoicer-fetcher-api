<?php
namespace App\Services\ApiManager\Operations;

use App\Services\ApiManager\Response\Entity\ApiResponse;
use App\Traits\User\UserTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class ApiRequestService extends BaseOperations
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
            return $this->getOperationResponse($this->providerName);
        }, $queryItems);
    }

    public function runOperation(array $query = []) {
        if (array_key_exists("query_type", $query) && $query["query_type"] === "array") {
            return $this->multipleQueryOperation($query);
        }
        $this->initialize($query);
        $providerName = null;
        if (!empty($this->providerName)) {
            $providerName = $this->providerName;
        }
        return $this->getOperationResponse('json', $providerName);
    }

    public function getOperationRequestContent(array $query = []) {
        $this->initialize($query);
        return $this->getOperationResponse('raw', $this->providerName);
    }

    public function setProviderName(string $providerName)
    {
        $this->providerName = $providerName;
    }

}
