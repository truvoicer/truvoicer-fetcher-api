<?php
namespace App\Services\ApiManager\Client;

use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use Exception;
use Illuminate\Support\Facades\Http;

class ApiClientHandler extends ApiBase
{

    private array $requestConfig;

    public function __construct()
    {
        $this->requestConfig = [];
    }

    /**
     * @throws Exception
     */
    public function sendRequest(ApiRequest $apiRequest)
    {
        try {
            $this->setQueryParams($apiRequest->getQuery());
            $this->setHeaders($apiRequest->getHeaders());
            $this->setPostData($apiRequest->getBody());
            $this->setRequestAuth($apiRequest->getAuthentication());


//            return $client->request($apiRequest->getMethod(), $apiRequest->getUrl(), $this->requestConfig);
            switch ($apiRequest->getMethod()) {
                case 'GET':
                    return Http::withHeaders($apiRequest->getHeaders())
                        ->withQueryParameters($apiRequest->getQuery())
                        ->get($apiRequest->getUrl());
                case 'POST':
                    return Http::withHeaders($apiRequest->getHeaders())
                        ->withBody($apiRequest->getBody())
                        ->post($apiRequest->getUrl());
                case 'PUT':
                    return Http::withHeaders($apiRequest->getHeaders())->put($apiRequest->getUrl(), $apiRequest->getBody());
                case 'DELETE':
                    return Http::withHeaders($apiRequest->getHeaders())->delete($apiRequest->getUrl());
                case 'PATCH':
                    return Http::withHeaders($apiRequest->getHeaders())->patch($apiRequest->getUrl(), $apiRequest->getBody());
            }
            return Http::withHeaders($apiRequest->getHeaders())->get($apiRequest->getUrl());

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function setRequestAuth(array $auth = [])
    {
        if (count($auth) > 0) {
            foreach ($auth as $key => $value) {
                $this->requestConfig[$key] = $value;
            }
        }
        return $this->requestConfig;
    }

    public function setQueryParams(array $params = [])
    {
        if (count($params) > 0) {
            $this->requestConfig["query"] = $params;
        }
        return $this->requestConfig;
    }

    public function setHeaders(array $headers = [])
    {
        if (count($headers) > 0) {
            $this->requestConfig['headers'] = $headers;
        }
        return $this->requestConfig;
    }

    public function setPostData($data = null) {
        if ($data !== null) {
            $this->requestConfig['body'] = $data;
        }
        return $this->requestConfig;
    }
}
