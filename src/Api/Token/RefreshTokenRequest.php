<?php

declare(strict_types=1);

namespace Doudian\Api\Token;

use Doudian\Core\AbstractRequest;

class RefreshTokenRequest extends AbstractRequest
{
    public function getUrlPath(): string
    {
        return '/token/refresh';
    }
} 