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
            $headers = $apiRequest->getHeaders();
            $client = null;
            if (array_key_exists('Content-Type', $headers)) {
                if ($headers['Content-Type'] === 'application/x-www-form-urlencoded') {
                    unset($headers['Content-Type']);
                    $client = Http::asForm();
                } else if ($headers['Content-Type'] === 'application/json') {
                    unset($headers['Content-Type']);
                    $client = Http::asJson();
                }
            }

            if ($client === null) {
                $client = Http::asJson();
            }
            $client->withHeaders($headers);
            $client = $this->addAuthentication($apiRequest, $client);
            if ($apiRequest->getBody()) {
                $client->withBody($apiRequest->getBody());
            }
            if ($apiRequest->getQuery()) {
                $client->withQueryParameters($apiRequest->getQuery());
            }

            return match ($apiRequest->getMethod()) {
                ApiRequest::METHOD_GET => $client
                    ->get($apiRequest->getUrl()),
                ApiRequest::METHOD_POST => $client
                    ->post($apiRequest->getUrl(), $apiRequest->getPostBody()),
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
            $password = '';
            if (!empty($authentication[ApiRequest::AUTH_DIGEST][ApiRequest::PASSWORD])) {
                $password = $authentication[ApiRequest::AUTH_DIGEST][ApiRequest::PASSWORD];
            }
            $client->withDigestAuth(
                $authentication[ApiRequest::AUTH_DIGEST][ApiRequest::USERNAME],
                $password
            );
        } elseif (isset($authentication[ApiRequest::AUTH_BASIC])) {
            $password = '';
            if (!empty($authentication[ApiRequest::AUTH_BASIC][ApiRequest::PASSWORD])) {
                $password = $authentication[ApiRequest::AUTH_BASIC][ApiRequest::PASSWORD];
            }
            $client->withBasicAuth(
                $authentication[ApiRequest::AUTH_BASIC][ApiRequest::USERNAME],
                $password
            );
        } elseif (isset($authentication[ApiRequest::AUTH_TOKEN])) {
            $client->withToken(
                $authentication[ApiRequest::AUTH_TOKEN][ApiRequest::TOKEN],
                $authentication[ApiRequest::AUTH_TOKEN][ApiRequest::TOKEN_TYPE]
            );
        }
        return $client;
    }

}
