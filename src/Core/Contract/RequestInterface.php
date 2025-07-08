<?php

declare(strict_types=1);

namespace Doudian\Core\Contract;

interface RequestInterface
{
    /**
     * 获取请求参数
     */
    public function getParam(): ?object;

    /**
     * 设置请求参数
     */
    public function setParam(object $param): void;

    /**
     * 获取 URL 路径
     */
    public function getUrlPath(): string;
} 