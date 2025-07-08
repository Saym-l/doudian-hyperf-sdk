<?php

declare(strict_types=1);

namespace Doudian\Api\Token\Param;

class RefreshTokenParam
{
    public string $grant_type = '';
    public string $refresh_token = '';
} 