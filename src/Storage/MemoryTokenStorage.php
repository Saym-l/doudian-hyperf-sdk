<?php

declare(strict_types=1);

namespace Doudian\Storage;

use Doudian\Core\Contract\TokenStorageInterface;

/**
 * 内存 Token 存储
 * 简单的内存存储实现，适用于开发环境或单进程应用
 * 注意：进程重启时数据会丢失
 */
class MemoryTokenStorage implements TokenStorageInterface
{
    protected array $tokens = [];

    /**
     * 存储访问令牌信息
     */
    public function store(int $shopId, array $tokenData, string $clientName = 'default'): bool
    {
        $key = $clientName . ':' . $shopId;
        $this->tokens[$key] = $tokenData;
        return true;
    }

    /**
     * 获取访问令牌信息
     */
    public function get(int $shopId, string $clientName = 'default'): ?array
    {
        $key = $clientName . ':' . $shopId;
        return $this->tokens[$key] ?? null;
    }

    /**
     * 删除访问令牌信息
     */
    public function delete(int $shopId, string $clientName = 'default'): bool
    {
        $key = $clientName . ':' . $shopId;
        if (isset($this->tokens[$key])) {
            unset($this->tokens[$key]);
            return true;
        }
        return false;
    }

    /**
     * 获取所有已授权的商家列表
     */
    public function list(string $clientName = 'default'): array
    {
        $result = [];
        foreach ($this->tokens as $key => $tokenData) {
            if (strpos($key, $clientName . ':') === 0) {
                $shopId = substr($key, strlen($clientName) + 1);
                $result[$shopId] = [
                    'shop_id' => $tokenData['shop_id'],
                    'shop_name' => $tokenData['shop_name'],
                    'scope' => $tokenData['scope'],
                    'authorized_at' => $tokenData['created_at'],
                    'updated_at' => $tokenData['updated_at'] ?? $tokenData['created_at'],
                    'expires_at' => $tokenData['expires_at'],
                    'is_expired' => $tokenData['expires_at'] <= time(),
                ];
            }
        }
        return $result;
    }

    /**
     * 检查商家是否已授权
     */
    public function exists(int $shopId, string $clientName = 'default'): bool
    {
        $key = $clientName . ':' . $shopId;
        return isset($this->tokens[$key]);
    }

    /**
     * 清空所有令牌（用于测试）
     */
    public function clear(): void
    {
        $this->tokens = [];
    }
} 