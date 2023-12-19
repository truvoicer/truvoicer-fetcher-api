<?php
namespace App\Services\ApiManager\Client\Entity;

class ApiRequest
{
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


    private string $method = "";
    private string $url = "";
    private array $headers = [];
    private array $query = [];
    private array $body = [];
    private array $authentication = [];

    /**
     * @return mixed
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param mixed $method
     */
    public function setMethod(string $method = ""): void
    {
        $this->method = strtoupper($method);
    }

    /**
     * @return mixed
     */
    public function getUrl()
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
     * @return mixed
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param mixed $headers
     */
    public function setHeaders(array $headers = []): void
    {
        $this->headers = $headers;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param mixed $query
     */
    public function setQuery(array $query = []): void
    {
        $this->query = $query;
    }

    /**
     * @return mixed
     */
    public function getAuthentication()
    {
        return $this->authentication;
    }

    /**
     * @param mixed $authentication
     */
    public function setAuthentication(array $authentication = []): void
    {
        $this->authentication = $authentication;
    }

    public function addBasicAuthentication(string $username, ?string $password = '') {
        $this->authentication[self::AUTH_BASIC] = [
          self::USERNAME => $username,
          self::PASSWORD => $password
        ];
    }
    public function addDigestAuthentication(string $username, ?string $password = '') {
        $this->authentication[self::AUTH_DIGEST] = [
          self::USERNAME => $username,
          self::PASSWORD => $password
        ];
    }
    public function addTokenAuthentication(string $token, ?string $tokenType = self::TOKEN_TYPE_BEARER) {
        $this->authentication[self::AUTH_TOKEN] = [
          self::TOKEN => $token,
          self::TOKEN_TYPE => $tokenType
        ];
    }

    /**
     * @return mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * @param mixed $body
     */
    public function setBody($body): void
    {
        $this->body = $body;
    }

    public function toArray(): array
    {
        return [
            "url" => $this->getUrl(),
            "headers" => $this->getHeaders(),
            "body" => $this->getBody(),
            "query" => $this->getQuery(),
            "method" => $this->getMethod(),
            "auth" => $this->getAuthentication(),
        ];
    }

}
