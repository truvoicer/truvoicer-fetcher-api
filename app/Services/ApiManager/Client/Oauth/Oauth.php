<?php

namespace App\Services\ApiManager\Client\Oauth;

use App\Exceptions\OauthResponseException;
use App\Models\Provider;
use App\Models\Sr;
use App\Repositories\OauthAccessTokenRepository;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\ApiManager\Data\DataConstants;
use App\Services\Provider\ProviderService;
use App\Services\Tools\SerializerService;
use DateTime;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Oauth extends ApiClientHandler
{
    public const ALLOWED_METHODS = ['GET', 'POST'];
    private Sr $sr;
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
    private ApiRequest $apiRequest;

    public function __construct(
        OauthAccessTokenRepository $oathTokenRepository,
        SerializerService $serializerService,
        ProviderService $providerService,
    ) {
        $this->oathTokenRepository = $oathTokenRepository;
        $this->serializerService = $serializerService;
        $this->providerService = $providerService;
        $this->apiRequest = new ApiRequest();
    }

    /**
     * @throws OauthResponseException
     * @throws \Exception
     */
    public function getAccessToken()
    {
        if (!isset($this->sr)) {
            return false;
        }
        $accessToken = $this->checkAccessToken();
        if ($accessToken !== null) {
            return $accessToken;
        }

        $response = $this->handleTokenResponse(
            $this->sendAccessTokenRequest()
        );

        return $this->setAccessToken(
            $response["access_token"],
            $this->getExpiryDatetime($response["expires_in"])
        );
    }


    private function handleTokenResponse(Response $response)
    {
        if ($response->status() !== 200) {
            throw new OauthResponseException(
                "Oauth response error",
                $response->status(),
                $response->json(),
                $this->apiRequest
            );
        }
        $response = $response->json();
        if (!isset($response["access_token"]) || !isset($response["expires_in"])) {
            throw new OauthResponseException(
                "Oauth response error",
                $response->status(),
                $response->json(),
                $this->apiRequest
            );
        }
        return $response;
    }

    /**
     * @throws \Exception
     */
    private function sendAccessTokenRequest(): PromiseInterface|Response
    {
        $this->setRequestData();
        return $this->sendRequest($this->apiRequest);
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
        if ($this->authType === DataConstants::AUTH_BASIC) {
            if (empty($this->username) || empty($this->password)) {
                throw new BadRequestHttpException("Username or password not set.");
            }
        }
        if ($this->authType === DataConstants::AUTH_BEARER) {
            if (empty($this->token)) {
                throw new BadRequestHttpException("Token not set.");
            }
        }
    }
    private function setRequestData(): void
    {
        $this->validateTokenRequest();

        $this->apiRequest->setMethod($this->method);
        $this->apiRequest->setUrl($this->url);

        switch ($this->authType) {
            case DataConstants::AUTH_BASIC:
                $this->apiRequest->addBasicAuthentication(
                    $this->username,
                    $this->password
                );
                break;
            case DataConstants::AUTH_BEARER:
                $this->apiRequest->addTokenAuthentication(
                    $this->token
                );
                break;
        }
        if (count($this->tokenRequestBody)) {
            $this->apiRequest->setBody($this->tokenRequestBody);
        }
        if (count($this->tokenRequestHeaders)) {
            $this->apiRequest->setHeaders($this->tokenRequestHeaders);
        }
        if (count($this->tokenRequestQuery)) {
            $this->apiRequest->setQuery($this->tokenRequestQuery);
        }
    }


    private function checkAccessToken()
    {
        return $this->oathTokenRepository->getLatestAccessToken($this->sr);
    }

    private function setAccessToken(string $access_token, DateTime $expiry): bool|Model
    {
        $insert = $this->oathTokenRepository->insertOathToken(
            $this->sr,
            $access_token,
            $expiry,
        );
        if (!$insert->exists) {
            return false;
        }
        return $insert;
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

    public function setSr(Sr $sr): void
    {
        $this->sr = $sr;
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
