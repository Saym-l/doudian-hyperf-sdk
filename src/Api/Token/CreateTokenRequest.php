<?php

declare(strict_types=1);

namespace Doudian\Api\Token;

use Doudian\Core\AbstractRequest;

class CreateTokenRequest extends AbstractRequest
{
    public function getUrlPath(): string
    {
        return '/token/create';
    }
} 