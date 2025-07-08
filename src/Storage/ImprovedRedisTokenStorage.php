<?php

declare(strict_types=1);

namespace Doudian\Storage;

use Doudian\Core\Contract\TokenStorageInterface;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

/**
 * 改进的 Redis Token 存储
 * 基于原有实现，增加了更多功能和性能优化
 */
class ImprovedRedisTokenStorage implements TokenStorageInterface
{
    protected \Redis $redis;
    protected string $keyPrefix;
    protected int $defaultTtl;
    protected int $bufferTime; // 刷新缓冲时间

    public function __construct(
        ContainerInterface $container, 
        string $keyPrefix = 'doudian:token:',
        int $defaultTtl = 7200,
        int $bufferTime = 86400,
        string $connection = 'default'
    ) {
        $redisFactory = $container->get(RedisFactory::class);
        $this->redis = $redisFactory->get($connection);
        $this->keyPrefix = $keyPrefix;
        $this->defaultTtl = $defaultTtl;
        $this->bufferTime = $bufferTime;
    }

    /**
     * 存储访问令牌信息
     */
    public function store(string $shopId, array $tokenData): bool
    {
        try {
            $key = $this->getTokenKey($shopId);
            $hashKey = $this->getHashKey();
            $shopListKey = $this->getShopListKey();
            
            // 准备存储数据
            $storeData = $this->prepareTokenData($tokenData);
            $serializedData = json_encode($storeData, JSON_UNESCAPED_UNICODE);
            
            // 计算 TTL
            $ttl = $this->calculateTtl($tokenData);
            
            // 使用 Pipeline 批量操作
            $pipe = $this->redis->multi(\Redis::PIPELINE);
            
            // 存储主要 token 数据
            $pipe->setex($key, $ttl, $serializedData);
            
            // 存储到 Hash 中用于快速查询
            $pipe->hset($hashKey, $shopId, $serializedData);
            $pipe->expire($hashKey, $ttl);
            
            // 添加到商家列表
            $pipe->sadd($shopListKey, $shopId);
            
            // 存储商家基本信息（用于快速列表显示）
            $shopInfoKey = $this->getShopInfoKey($shopId);
            $shopInfo = [
                'shop_id' => $shopId,
                'shop_name' => $tokenData['shop_name'] ?? '',
                'scope' => $tokenData['scope'] ?? '',
                'authorized_at' => $tokenData['created_at'] ?? time(),
                'updated_at' => time(),
                'expires_at' => $tokenData['expires_at'] ?? (time() + $this->defaultTtl),
            ];
            $pipe->setex($shopInfoKey, $ttl, json_encode($shopInfo, JSON_UNESCAPED_UNICODE));
            
            $results = $pipe->exec();
            
            return !empty($results) && $results[0] !== false;
            
        } catch (\Exception $e) {
            error_log("Redis token storage error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取访问令牌信息
     */
    public function get(string $shopId): ?array
    {
        try {
            $key = $this->getTokenKey($shopId);
            $data = $this->redis->get($key);
            
            if ($data === false) {
                // 从 Hash 中尝试获取
                $hashKey = $this->getHashKey();
                $data = $this->redis->hget($hashKey, $shopId);
                
                if ($data === false) {
                    return null;
                }
            }
            
            $tokenData = json_decode($data, true);
            
            if (!is_array($tokenData)) {
                return null;
            }
            
            // 检查是否即将过期，如果是则更新 TTL
            $this->checkAndUpdateExpiry($shopId, $tokenData);
            
            return $tokenData;
            
        } catch (\Exception $e) {
            error_log("Redis token get error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 删除访问令牌信息
     */
    public function delete(string $shopId): bool
    {
        try {
            $pipe = $this->redis->multi(\Redis::PIPELINE);
            
            // 删除主 token 数据
            $pipe->del($this->getTokenKey($shopId));
            
            // 从 Hash 中删除
            $pipe->hdel($this->getHashKey(), $shopId);
            
            // 从商家列表中移除
            $pipe->srem($this->getShopListKey(), $shopId);
            
            // 删除商家信息
            $pipe->del($this->getShopInfoKey($shopId));
            
            $results = $pipe->exec();
            
            return !empty($results) && $results[0] > 0;
            
        } catch (\Exception $e) {
            error_log("Redis token delete error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取所有已授权的商家列表
     */
    public function list(): array
    {
        try {
            $shopListKey = $this->getShopListKey();
            $shopIds = $this->redis->smembers($shopListKey);
            
            if (empty($shopIds)) {
                return [];
            }
            
            // 批量获取商家信息
            $shops = [];
            $pipe = $this->redis->multi(\Redis::PIPELINE);
            
            foreach ($shopIds as $shopId) {
                $pipe->get($this->getShopInfoKey($shopId));
            }
            
            $results = $pipe->exec();
            
            foreach ($shopIds as $index => $shopId) {
                $shopInfoData = $results[$index] ?? false;
                
                if ($shopInfoData !== false) {
                    $shopInfo = json_decode($shopInfoData, true);
                    if (is_array($shopInfo)) {
                        $shopInfo['is_expired'] = ($shopInfo['expires_at'] ?? 0) <= time();
                        $shops[$shopId] = $shopInfo;
                    }
                }
            }
            
            // 清理过期的商家
            $this->cleanupExpiredShops($shops);
            
            return $shops;
            
        } catch (\Exception $e) {
            error_log("Redis token list error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 检查商家是否已授权
     */
    public function exists(string $shopId): bool
    {
        try {
            $key = $this->getTokenKey($shopId);
            return $this->redis->exists($key) > 0;
        } catch (\Exception $e) {
            error_log("Redis token exists error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 批量获取多个商家的 token（性能优化）
     */
    public function batchGet(array $shopIds): array
    {
        if (empty($shopIds)) {
            return [];
        }

        try {
            $pipe = $this->redis->multi(\Redis::PIPELINE);
            
            foreach ($shopIds as $shopId) {
                $pipe->get($this->getTokenKey($shopId));
            }
            
            $results = $pipe->exec();
            $tokens = [];
            
            foreach ($shopIds as $index => $shopId) {
                $data = $results[$index] ?? false;
                if ($data !== false) {
                    $tokenData = json_decode($data, true);
                    if (is_array($tokenData)) {
                        $tokens[$shopId] = $tokenData;
                    }
                }
            }
            
            return $tokens;
            
        } catch (\Exception $e) {
            error_log("Redis batch get error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 清理所有过期的 token
     */
    public function cleanup(): int
    {
        try {
            $shops = $this->list();
            $cleaned = 0;
            
            foreach ($shops as $shopId => $shopInfo) {
                if ($shopInfo['is_expired']) {
                    if ($this->delete($shopId)) {
                        $cleaned++;
                    }
                }
            }
            
            return $cleaned;
            
        } catch (\Exception $e) {
            error_log("Redis cleanup error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * 获取存储统计信息
     */
    public function getStats(): array
    {
        try {
            $shopListKey = $this->getShopListKey();
            $totalShops = $this->redis->scard($shopListKey);
            
            $shops = $this->list();
            $activeShops = count(array_filter($shops, fn($shop) => !$shop['is_expired']));
            $expiredShops = $totalShops - $activeShops;
            
            // 获取 Redis 内存使用情况
            $info = $this->redis->info('memory');
            $memoryUsed = $info['used_memory_human'] ?? 'N/A';
            
            return [
                'total_shops' => $totalShops,
                'active_shops' => $activeShops,
                'expired_shops' => $expiredShops,
                'memory_used' => $memoryUsed,
                'key_prefix' => $this->keyPrefix,
                'default_ttl' => $this->defaultTtl,
                'buffer_time' => $this->bufferTime,
            ];
            
        } catch (\Exception $e) {
            error_log("Redis stats error: " . $e->getMessage());
            return [];
        }
    }

    // ===== 私有辅助方法 =====

    protected function getTokenKey(string $shopId): string
    {
        return $this->keyPrefix . $shopId;
    }

    protected function getHashKey(): string
    {
        return $this->keyPrefix . 'hash';
    }

    protected function getShopListKey(): string
    {
        return $this->keyPrefix . 'shops';
    }

    protected function getShopInfoKey(string $shopId): string
    {
        return $this->keyPrefix . 'info:' . $shopId;
    }

    protected function prepareTokenData(array $tokenData): array
    {
        return array_merge($tokenData, [
            'stored_at' => time(),
            'updated_at' => time(),
        ]);
    }

    protected function calculateTtl(array $tokenData): int
    {
        $expiresIn = $tokenData['expires_in'] ?? $this->defaultTtl;
        return $expiresIn + $this->bufferTime; // 加上缓冲时间
    }

    protected function checkAndUpdateExpiry(string $shopId, array $tokenData): void
    {
        $expiresAt = $tokenData['expires_at'] ?? 0;
        $timeToExpiry = $expiresAt - time();
        
        // 如果在 30 分钟内过期，延长 Redis key 的 TTL
        if ($timeToExpiry <= 1800 && $timeToExpiry > 0) {
            $key = $this->getTokenKey($shopId);
            $this->redis->expire($key, 7200); // 延长 2 小时
        }
    }

    protected function cleanupExpiredShops(array $shops): void
    {
        $expiredShops = array_filter($shops, fn($shop) => $shop['is_expired']);
        
        if (!empty($expiredShops)) {
            foreach ($expiredShops as $shopId => $shop) {
                $this->delete($shopId);
            }
        }
    }
} 