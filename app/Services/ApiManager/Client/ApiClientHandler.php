<?php

namespace App\Services\ApiManager\Client;

use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class ApiClientHandler extends ApiBase
{

    /**
     * @throws Exception
     */
    public function sendRequest(ApiRequest $apiRequest)
    {
        try {
            $client = $this->addAuthentication(
                $apiRequest,
                Http::withHeaders($apiRequest->getHeaders())
            );

            return match ($apiRequest->getMethod()) {
                ApiRequest::METHOD_GET => $client
                    ->withQueryParameters($apiRequest->getQuery())
                    ->get($apiRequest->getUrl()),
                ApiRequest::METHOD_POST => $client
                    ->withBody($apiRequest->getBody())
                    ->post($apiRequest->getUrl()),
                default => throw new Exception('Invalid method'),
            };
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function addAuthentication(ApiRequest $apiRequest, PendingRequest $client)
    {
        $authentication = $apiRequest->getAuthentication();
        if (!count($authentication)) {
            return $client;
        }
        if (isset($authentication[ApiRequest::AUTH_DIGEST])) {
            $client->withDigestAuth(
                $authentication[ApiRequest::AUTH_DIGEST][ApiRequest::USERNAME],
                $authentication[ApiRequest::AUTH_DIGEST][ApiRequest::PASSWORD]
            );
        } elseif (isset($authentication[ApiRequest::AUTH_BASIC])) {
            $client->withBasicAuth(
                $authentication[ApiRequest::AUTH_BASIC][ApiRequest::USERNAME],
                $authentication[ApiRequest::AUTH_BASIC][ApiRequest::PASSWORD]
            );
        } elseif (isset($authentication[ApiRequest::AUTH_TOKEN])) {
            $client->withToken(
                $authentication[ApiRequest::AUTH_TOKEN][ApiRequest::TOKEN],
                $authentication[ApiRequest::AUTH_DIGEST][ApiRequest::TOKEN_TYPE]
            );
        }
        return $client;
    }

}
