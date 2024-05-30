<?php

namespace App\Services\ApiManager\Client\Oauth;

use App\Models\Provider;
use App\Models\SrConfig;
use App\Repositories\OauthAccessTokenRepository;
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
    private ?Provider $provider = null;
    private ProviderService $providerService;
    private SerializerService $serializerService;
    private OauthAccessTokenRepository $oathTokenRepository;

    private array $tokenRequestHeaders;
    private array $tokenRequestBody;
    private array $tokenRequestQuery;

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
        $response = $this->sendRequest($this->setRequestData());
        if ($response->status() !== 200) {
            throw new BadRequestHttpException("Error retrieving access token.");
        }
        return $response->json(true);
    }

    private function setRequestData()
    {
        $apiRequest = new ApiRequest();

        $grantTypeName = $this->getPropertyValue(self::OAUTH_GRANT_TYPE_FIELD_NAME);
        $grantTypeValue = $this->getPropertyValue(self::OAUTH_GRANT_TYPE_FIELD_VALUE);
        $scopeName = $this->getPropertyValue(self::OAUTH_SCOPE_FIELD_NAME);
        $scopeValue = $this->getPropertyValue(self::OAUTH_SCOPE_FIELD_VALUE);
        $accessTokenValue = $this->getPropertyValue(self::ACCESS_TOKEN);
        $secretKeyValue = $this->getPropertyValue(self::SECRET_KEY);

        $apiRequest->setMethod("POST");
        $apiRequest->setUrl($this->getPropertyValue(self::OAUTH_TOKEN_URL_KEY));


        switch ($this->getPropertyValue(self::API_AUTH_TYPE)) {
            case "oauth":
            case "oauth_basic":
                $apiRequest->addBasicAuthentication(
                    $accessTokenValue,
                    $secretKeyValue
                );
                break;
            case "oauth_body":
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

}
