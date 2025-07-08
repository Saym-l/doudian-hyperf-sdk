<?php

declare(strict_types=1);

namespace Doudian\Api\Product;

use Doudian\Core\AbstractRequest;

class ProductListRequest extends AbstractRequest
{
    public function getUrlPath(): string
    {
        return '/product/listV2';
    }
} 