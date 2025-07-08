<?php

declare(strict_types=1);

namespace Doudian\Core\Contract;

interface TokenStorageInterface
{
    /**
     * 存储访问令牌信息
     */
    public function store(string $shopId, array $tokenData): bool;

    /**
     * 获取访问令牌信息
     */
    public function get(string $shopId): ?array;

    /**
     * 删除访问令牌信息
     */
    public function delete(string $shopId): bool;

    /**
     * 获取所有已授权的商家列表
     */
    public function list(): array;

    /**
     * 检查商家是否已授权
     */
    public function exists(string $shopId): bool;
} 