<?php
namespace App\Services\ApiManager\Client\Entity;

class ApiRequest
{
    private $method = "";
    private $url = "";
    private $headers = [];
    private $query = [];
    private $body = null;
    private $authentication = [];

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
