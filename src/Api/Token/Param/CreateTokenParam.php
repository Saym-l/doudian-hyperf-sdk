<?php

declare(strict_types=1);

namespace Doudian\Api\Token\Param;

class CreateTokenParam
{
    public string $grant_type = '';
    public string $code = '';
    public string $shop_id = '';
} 