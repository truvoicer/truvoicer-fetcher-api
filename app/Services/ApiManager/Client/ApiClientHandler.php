<?php

namespace App\Services\ApiManager\Client;

use App\Enums\Api\ApiType;
use App\Services\Ai\DeepSeek\DeepSeekClient;
use App\Services\Ai\Gemini\GeminiClient;
use App\Services\Ai\Grok\GrokClient;
use App\Services\Ai\OpenAi\OpenAiClient;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class ApiClientHandler extends ApiBase
{

    /**
     * @throws Exception
     */
    public function sendRequest(ApiRequest $apiRequest): Response|null
    {
        switch ($apiRequest->getApiType()) {
            case ApiType::AI_DEEP_SEEK:
                return $this->sendAiDeepSeekRequest($apiRequest);
            case ApiType::AI_GEMINI:
                return $this->sendAiGeminiRequest($apiRequest);
            case ApiType::AI_OPEN_AI:
                return $this->sendOpenAiRequest($apiRequest);
            case ApiType::AI_GROK:
                return $this->sendAiGrokRequest($apiRequest);
            default:
                return $this->sendDefaultRequest($apiRequest);
        }
    }

    /**
     * @throws Exception
     */
    public function sendAiGeminiRequest(ApiRequest $apiRequest): Response
    {
        $geminiService = app(GeminiClient::class);
        $geminiService
            ->setPrompt($apiRequest->getAiPrompt())
            ->setApiKey($apiRequest->getAccessToken())
            ->makeRequest();
        $url = $apiRequest->getUrl();
        if (!empty($url)) {
            $geminiService->setApiEndpoint($url);
        }
        return $geminiService->makeRequest();
    }

    /**
     * @throws Exception
     */
    public function sendAiDeepSeekRequest(ApiRequest $apiRequest): Response
    {
        $deepSeekService = app(DeepSeekClient::class);
        $deepSeekService
            ->setPrompt($apiRequest->getAiPrompt())
            ->setApiKey($apiRequest->getAccessToken());
        $url = $apiRequest->getUrl();
        if (!empty($url)) {
            $deepSeekService->setApiEndpoint($url);
        }
        $temperature = $apiRequest->getAiTemperature();
        if ($temperature !== null) {
            $deepSeekService->setTemperature((float)$temperature);
        }
        $systemPrompt = $apiRequest->getAiSystemPrompt();
        if ($systemPrompt) {
            $deepSeekService->setSystemPrompt($systemPrompt);
        }
        return $deepSeekService->makeRequest();
    }
    /**
     * @throws Exception
     */
    public function sendAiGrokRequest(ApiRequest $apiRequest): Response
    {
        $deepSeekService = app(GrokClient::class);
        $deepSeekService
            ->setPrompt($apiRequest->getAiPrompt())
            ->setApiKey($apiRequest->getAccessToken())
            ->setWebSearch(true);
        $url = $apiRequest->getUrl();
        if (!empty($url)) {
            $deepSeekService->setApiEndpoint($url);
        }
        $temperature = $apiRequest->getAiTemperature();
        if ($temperature !== null) {
            $deepSeekService->setTemperature((float)$temperature);
        }
        $systemPrompt = $apiRequest->getAiSystemPrompt();
        if ($systemPrompt) {
            $deepSeekService->setSystemPrompt($systemPrompt);
        }
        return $deepSeekService->makeRequest();
    }

    /**
     * @throws Exception
     */
    public function sendOpenAiRequest(ApiRequest $apiRequest): Response
    {
        $openAiService = app(OpenAiClient::class);
        $openAiService
            ->setPrompt($apiRequest->getAiPrompt())
            ->setApiKey($apiRequest->getAccessToken());
        $url = $apiRequest->getUrl();
        if (!empty($url)) {
            $openAiService->setApiEndpoint($url);
        }
        return $openAiService->makeRequest();
    }

    /**
     * @throws Exception
     */
    public function sendDefaultRequest(ApiRequest $apiRequest): Response
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
