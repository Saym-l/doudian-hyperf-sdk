<?php

declare(strict_types=1);

namespace Doudian\Api\Product\Param;

class ProductListParam
{
    public int $page = 0;
    public int $size = 10;
    public ?int $status = null;
    public ?int $check_status = null;
    public ?string $title = null;
    public ?string $product_id = null;
    public ?int $create_time_start = null;
    public ?int $create_time_end = null;
    public ?int $update_time_start = null;
    public ?int $update_time_end = null;
} 