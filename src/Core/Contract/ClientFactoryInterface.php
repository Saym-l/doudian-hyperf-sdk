<?php

declare(strict_types=1);

namespace Doudian\Core\Contract;

use Doudian\Core\DoudianClient;

interface ClientFactoryInterface
{
    /**
     * 创建默认抖店客户端
     */
    public function create(): DoudianClient;

    /**
     * 创建指定名称的抖店客户端
     */
    public function get(string $name): DoudianClient;
} 