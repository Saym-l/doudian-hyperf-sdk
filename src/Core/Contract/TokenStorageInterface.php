<?php

declare(strict_types=1);

namespace Doudian\Core\Contract;

interface TokenStorageInterface
{
    /**
     * 存储访问令牌信息
     */
    public function store(int $shopId, array $tokenData, string $clientName = 'default'): bool;

    /**
     * 获取访问令牌信息
     */
    public function get(int $shopId, string $clientName = 'default'): ?array;

    /**
     * 删除访问令牌信息
     */
    public function delete(int $shopId, string $clientName = 'default'): bool;

    /**
     * 获取所有已授权的商家列表
     */
    public function list(string $clientName = 'default'): array;

    /**
     * 检查商家是否已授权
     */
    public function exists(int $shopId, string $clientName = 'default'): bool;
} 