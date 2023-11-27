<?php
namespace App\Services\ApiManager\Client\Oauth;

use App\Models\OauthAccessTokens;
use App\Models\Provider;
use App\Repositories\OauthAccessTokensRepository;
use App\Services\ApiManager\Client\ApiClientHandler;
use App\Services\ApiManager\Client\Entity\ApiRequest;
use App\Services\Provider\ProviderService;
use App\Services\Tools\SerializerService;
use DateTime;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Oauth extends ApiClientHandler
{
    private $provider = null;
    private $providerService;
    private $serializerService;
    private $oathTokenRepository;

    public function __construct(OauthAccessTokensRepository $oathTokenRepository, SerializerService $serializerService,
                                ProviderService $providerService)
    {
        parent::__construct();
        $this->oathTokenRepository = $oathTokenRepository;
        $this->serializerService = $serializerService;
        $this->providerService = $providerService;
    }

    public function getAccessToken() {
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

    private function sendAccessTokenRequest() {

        $response = $this->sendRequest($this->setRequestData());
        if ($response->getStatusCode() !== 200) {
            throw new BadRequestHttpException("Error retrieving access token.");
        }
        return $response->toArray(true);
    }

    private function setRequestData() {
        $apiRequest = new ApiRequest();

        $grantTypeName = $this->getPropertyValue(self::OAUTH_GRANT_TYPE_FIELD_NAME);
        $grantTypeValue = $this->getPropertyValue(self::OAUTH_GRANT_TYPE_FIELD_VALUE);
        $scopeName = $this->getPropertyValue(self::OAUTH_SCOPE_FIELD_NAME);
        $scopeValue = $this->getPropertyValue(self::OAUTH_SCOPE_FIELD_VALUE);

        $apiRequest->setMethod("POST");
        $apiRequest->setUrl($this->getPropertyValue(self::OAUTH_TOKEN_URL_KEY));
        $apiRequest->setHeaders(['Content-Type' => 'application/x-www-form-urlencoded']);

        switch ($this->getPropertyValue(self::API_AUTH_TYPE)) {
            case "oauth":
            case "oauth_basic":
                $apiRequest->setAuthentication([
                    "auth_basic" => [
                        $this->provider->getProviderAccessKey(),
                        $this->provider->getProviderSecretKey()
                    ]
                ]);
                $apiRequest->setBody([
                    $grantTypeName => $grantTypeValue,
                    $scopeName => $scopeValue
                ]);
                break;
            case "oauth_body":
                $apiRequest->setBody([
                    $grantTypeName => $grantTypeValue,
                    "client_id" => $this->provider->getProviderAccessKey(),
                    "client_secret" => $this->provider->getProviderSecretKey()
                ]);
                break;
        }
        return $apiRequest;
    }

    private function getPropertyValue(string $propertyName) {
        return $this->providerService->getProviderPropertyValue($this->provider, $propertyName);
    }

    private function checkAccessToken() {
        return $this->oathTokenRepository->getLatestAccessToken($this->provider);
    }

    private function setAccessToken(string $access_token, DateTime $expiry) {
        return $this->oathTokenRepository->saveOathToken(
            $this->setOathTokenObject(new OauthAccessTokens(), $access_token, $expiry), $this->provider);
    }

    private function setOathTokenObject(OauthAccessTokens $oathToken, string $access_token, \DateTime $expiry) {
        $oathToken->setAccessToken($access_token);
        $oathToken->setExpiry($expiry);
        return $oathToken;
    }

    private function getExpiryDatetime(int $expirySeconds) {
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
