<?php

declare(strict_types=1);

namespace Doudian\Core\Http;

class HttpResponse
{
    public int $statusCode = 200;
    
    public string $body = '';
    
    public array $headers = [];

    public function __construct(int $statusCode = 200, string $body = '', array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function getJsonData(): array
    {
        if (empty($this->body)) {
            return [];
        }
        
        $data = json_decode($this->body, true);
        return is_array($data) ? $data : [];
    }
} 