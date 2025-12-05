<?php
namespace App\Services\ApiManager\Client\Entity;

use App\Enums\Api\Manager\ApiClientRequestType;
use App\Traits\ObjectTrait;

class ApiRequest
{
    use ObjectTrait;
    public const METHOD_POST = 'POST';
    public const METHOD_GET = 'GET';
    public const AUTH_TOKEN = 'auth_token';
    public const AUTH_BASIC = 'auth_basic';
    public const AUTH_DIGEST = 'auth_digest';
    public const TOKEN = 'token';
    public const TOKEN_TYPE = 'type';
    public const TOKEN_TYPE_BEARER = 'Bearer';
    public const USERNAME = 'username';
    public const PASSWORD = 'password';


    private string $accessToken;
    private string $method = "";
    private string $url = "";
    private array $headers = [];
    private array $query = [];
    private array $postBody = [];
    private ?string $body = null;
    private array $authentication = [];

    private ApiClientRequestType $apiClientRequestType = ApiClientRequestType::DEFAULT;
    /**
     * @return ApiClientRequestType
     */
    public function getApiClientRequestType(): ApiClientRequestType
    {
        return $this->apiClientRequestType;
    }

    /**
     * @param ApiClientRequestType $apiClientRequestType
     */
    public function setApiClientRequestType(
        ApiClientRequestType $apiClientRequestType
    ): static
    {
        $this->apiClientRequestType = $apiClientRequestType;
        return $this;
    }


    /**
     * @var string|null The AI prompt text.
     */
    private ?string $aiPrompt = null;

    /**
     * @var string|null The AI System prompt text.
     */
    private ?string $aiSystemPrompt = null;

    /**
     * @var int|float|null The AI Ai Temperature text.
     */
    private int|float|null $aiTemperature = null;

    /**
     * Retrieves the current value of the AI prompt.
     * * @return string|null The AI prompt string, or null if it has not been set.
     */
    public function getAiPrompt(): ?string
    {
        return $this->aiPrompt;
    }

    /**
     * Sets the value of the AI prompt.
     * * @param string $aiPrompt The new AI prompt string.
     * @return void
     */
    public function setAiPrompt(string $aiPrompt): void
    {
        $this->aiPrompt = $aiPrompt;
    }

    /**
     * Retrieves the current value of the AI prompt.
     * * @return string|null The AI prompt string, or null if it has not been set.
     */
    public function getAiSystemPrompt(): ?string
    {
        return $this->aiSystemPrompt;
    }

    /**
     * Sets the value of the AI prompt.
     * * @param string $aiPrompt The new AI prompt string.
     * @return void
     */
    public function setAiSystemPrompt(string $aiSystemPrompt): void
    {
        $this->aiSystemPrompt = $aiSystemPrompt;
    }

    /**
     * Retrieves the current value of the AI Temperature.
     * * @return int|float|null The AI Temperature, or null if it has not been set.
     */
    public function getAiTemperature(): int|float|null
    {
        return $this->aiTemperature;
    }

    /**
     * Sets the value of the AI temperature.
     * * @param int|float|null  $aiPrompt The new AI temperature.
     * @return void
     */
    public function setAiTemperature(int|float|null $aiTemperature): void
    {
        $this->aiTemperature = $aiTemperature;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    /**
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @param string $method
     */
    public function setMethod(string $method = ""): void
    {
        $this->method = strtoupper($method);
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param mixed $url
     */
    public function setUrl(string $url = ""): void
    {
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders(array $headers = []): void
    {
        $this->headers = $headers;
    }

    /**
     * @return array
     */
    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @param array $query
     */
    public function setQuery(array $query = []): void
    {
        $this->query = $query;
    }

    /**
     * @return array
     */
    public function getAuthentication(): array
    {
        return $this->authentication;
    }

    /**
     * @param array $authentication
     */
    public function setAuthentication(array $authentication = []): void
    {
        $this->authentication = $authentication;
    }

    public function addBasicAuthentication(string $username, ?string $password = ''): void {
        $this->authentication[self::AUTH_BASIC] = [
          self::USERNAME => $username,
          self::PASSWORD => $password
        ];
    }
    public function addDigestAuthentication(string $username, ?string $password = ''): void {
        $this->authentication[self::AUTH_DIGEST] = [
          self::USERNAME => $username,
          self::PASSWORD => $password
        ];
    }
    public function addTokenAuthentication(string $token, ?string $tokenType = self::TOKEN_TYPE_BEARER): void {
        $this->authentication[self::AUTH_TOKEN] = [
          self::TOKEN => $token,
          self::TOKEN_TYPE => $tokenType
        ];
    }

    /**
     * @return array
     */
    public function getPostBody(): array
    {
        return $this->postBody;
    }

    /**
     * @param array $postBody
     */
    public function setPostBody(array $postBody): void
    {
        $this->postBody = $postBody;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function toArray(): array
    {
        return [
            "url" => $this->getUrl(),
            "headers" => $this->getHeaders(),
            "post_body" => $this->getPostBody(),
            "body" => $this->getBody(),
            "query" => $this->getQuery(),
            "method" => $this->getMethod(),
            "auth" => $this->getAuthentication(),
        ];
    }

}
