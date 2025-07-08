<?php

declare(strict_types=1);

namespace Doudian\Core\Http;

class HttpRequest
{
    public string $url = '';
    
    public string $body = '';
    
    public int $connectTimeout = 3;
    
    public int $readTimeout = 10;
    
    public array $headers = [];
    
    public string $method = 'POST';

    public function __construct(
        string $url = '',
        string $body = '',
        int $connectTimeout = 3,
        int $readTimeout = 10,
        array $headers = [],
        string $method = 'POST'
    ) {
        $this->url = $url;
        $this->body = $body;
        $this->connectTimeout = $connectTimeout;
        $this->readTimeout = $readTimeout;
        $this->headers = $headers;
        $this->method = $method;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    public function addHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setTimeout(int $connectTimeout, int $readTimeout): self
    {
        $this->connectTimeout = $connectTimeout;
        $this->readTimeout = $readTimeout;
        return $this;
    }
} 