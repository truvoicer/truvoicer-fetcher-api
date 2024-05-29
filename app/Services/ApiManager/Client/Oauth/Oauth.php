<?php

namespace App\Services\ApiManager\Client\Oauth;

use App\Models\Provider;
use App\Repositories\OauthAccessTokenRepository;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\Provider\ProviderService;
use App\Services\Tools\SerializerService;
use DateTime;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Oauth extends ApiClientHandler
{
    private ?Provider $provider = null;
    private ProviderService $providerService;
    private SerializerService $serializerService;
    private OauthAccessTokenRepository $oathTokenRepository;

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
        $clientIdValue = $this->getPropertyValue(self::CLIENT_ID);
        $clientSecretValue = $this->getPropertyValue(self::CLIENT_SECRET);
        $accessTokenValue = $this->getPropertyValue(self::ACCESS_TOKEN);
        $secretKeyValue = $this->getPropertyValue(self::SECRET_KEY);

        $apiRequest->setMethod("POST");
        $apiRequest->setUrl($this->getPropertyValue(self::OAUTH_TOKEN_URL_KEY));
        $apiRequest->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);

        switch ($this->getPropertyValue(self::API_AUTH_TYPE)) {
            case "oauth":
            case "oauth_basic":
                $apiRequest->addBasicAuthentication(
                    $accessTokenValue,
                    $secretKeyValue
                );
                $apiRequest->setBody([
                    $grantTypeName => $grantTypeValue,
                    $scopeName => $scopeValue
                ]);
                break;
            case "oauth_body":
                $apiRequest->setBody([
                    $grantTypeName => $grantTypeValue,
                    "client_id" => $clientIdValue,
                    "client_secret" => $clientSecretValue
                ]);
                break;
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
}
