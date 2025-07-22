<?php

declare(strict_types=1);

namespace Doudian\Core;

class DoudianTokenRefreshedEvent
{
    public int $shopId;
    public string $clientName;
    public AccessToken $accessToken;

    public function __construct(int $shopId, string $clientName, AccessToken $accessToken)
    {
        $this->shopId = $shopId;
        $this->clientName = $clientName;
        $this->accessToken = $accessToken;
    }
} 