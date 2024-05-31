<?php

namespace App\Services\ApiManager\Client\Oauth;

use App\Exceptions\OauthResponseException;
use App\Models\Provider;
use App\Models\SrConfig;
use App\Repositories\OauthAccessTokenRepository;
use App\Services\ApiManager\ApiBase;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Data\DataProcessor;
use App\Services\Provider\ProviderService;
use App\Services\Tools\SerializerService;
use DateTime;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Oauth extends ApiClientHandler
{
    public const ALLOWED_METHODS = ['GET', 'POST'];
    private ?Provider $provider = null;
    private ProviderService $providerService;
    private SerializerService $serializerService;
    private OauthAccessTokenRepository $oathTokenRepository;

    private array $tokenRequestHeaders;
    private array $tokenRequestBody;
    private array $tokenRequestQuery;
    private string $authType;
    private string $username;
    private string $password;
    private string $token;
    private string $method;
    private string $url;

    public function __construct(
        OauthAccessTokenRepository $oathTokenRepository,
        SerializerService $serializerService,
        ProviderService $providerService
    ) {
        $this->oathTokenRepository = $oathTokenRepository;
        $this->serializerService = $serializerService;
        $this->providerService = $providerService;
    }

    public function getAccessToken()
    {
        if ($this->provider === null) {
            return false;
        }
        $accessToken = $this->checkAccessToken();
        if ($accessToken !== null) {
            return $accessToken;
        }
        $sendRequest = $this->sendAccessTokenRequest();

        return $this->setAccessToken(
            $sendRequest["access_token"],
            $this->getExpiryDatetime($sendRequest["expires_in"])
        );
    }

    /**
     * @throws \Exception
     */
    private function sendAccessTokenRequest()
    {
        $request  = $this->setRequestData();
        $response = $this->sendRequest($request);
        if ($response->status() !== 200) {
            throw new OauthResponseException(
                "Oauth response error",
                $response->status(),
                $response->json(),
                $request
            );
        }
        return $response->json();
    }

    private function validateTokenRequest()
    {
        if (empty($this->provider) || !($this->provider instanceof Provider)) {
            throw new BadRequestHttpException("Provider not set.");
        }

        if (empty($this->url)) {
            throw new BadRequestHttpException("Url not set.");
        }

        if (empty($this->method)) {
            throw new BadRequestHttpException("Method not set.");
        }

        if (!in_array($this->method, self::ALLOWED_METHODS)) {
            throw new BadRequestHttpException("Method not allowed.");
        }

        if (empty($this->authType)) {
            throw new BadRequestHttpException("Auth type not set.");
        }
        if ($this->authType === ApiBase::AUTH_BASIC) {
            if (empty($this->username) || empty($this->password)) {
                throw new BadRequestHttpException("Username or password not set.");
            }
        }
        if ($this->authType === ApiBase::AUTH_BEARER) {
            if (empty($this->token)) {
                throw new BadRequestHttpException("Token not set.");
            }
        }
    }
    private function setRequestData(): ApiRequest
    {
        $this->validateTokenRequest();

        $apiRequest = new ApiRequest();

        $apiRequest->setMethod($this->method);
        $apiRequest->setUrl($this->url);

        switch ($this->authType) {
            case ApiBase::AUTH_BASIC:
                $apiRequest->addBasicAuthentication(
                    $this->username,
                    $this->password
                );
                break;
            case ApiBase::AUTH_BEARER:
                $apiRequest->addTokenAuthentication(
                    $this->token
                );
                break;
        }
        if (count($this->tokenRequestBody)) {
            $apiRequest->setBody($this->tokenRequestBody);
        }
        if (count($this->tokenRequestHeaders)) {
            $apiRequest->setHeaders($this->tokenRequestHeaders);
        }
        if (count($this->tokenRequestQuery)) {
            $apiRequest->setQuery($this->tokenRequestQuery);
        }
        return $apiRequest;
    }

    private function getPropertyValue(string $propertyName)
    {
        return $this->providerService->getProviderPropertyValue($this->provider, $propertyName);
    }

    private function checkAccessToken()
    {
        return $this->oathTokenRepository->getLatestAccessToken($this->provider);
    }

    private function setAccessToken(string $access_token, DateTime $expiry)
    {
        $insert = $this->oathTokenRepository->insertOathToken(
            $access_token,
            $expiry,
            $this->provider
        );
        if (!$insert) {
            return false;
        }
        return $this->oathTokenRepository->getModel();
    }

    private function getExpiryDatetime(int $expirySeconds)
    {
        $expiryDate = new DateTime();
        return $expiryDate->setTimestamp(time() + $expirySeconds);
    }


    /**
     * @param mixed $provider
     */
    public function setProvider(Provider $provider): void
    {
        $this->provider = $provider;
    }

    public function setTokenRequestHeaders(array $tokenRequestHeaders): void
    {
        $this->tokenRequestHeaders = $tokenRequestHeaders;
    }

    public function setTokenRequestBody(array $tokenRequestBody): void
    {
        $this->tokenRequestBody = $tokenRequestBody;
    }

    public function setTokenRequestQuery(array $tokenRequestQuery): void
    {
        $this->tokenRequestQuery = $tokenRequestQuery;
    }

    public function setAuthType(string $authType): void
    {
        $this->authType = $authType;
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function setMethod(string $method): void
    {
        $this->method = strtoupper($method);
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

}
