<?php

declare(strict_types=1);

namespace Doudian\Storage;

use Doudian\Core\Contract\TokenStorageInterface;
use Hyperf\Redis\RedisFactory;
use Psr\Container\ContainerInterface;

class RedisTokenStorage implements TokenStorageInterface
{
    protected \Redis $redis;
    protected string $keyPrefix;

    public function __construct(ContainerInterface $container, string $keyPrefix = 'doudian:token:')
    {
        $redisFactory = $container->get(RedisFactory::class);
        $this->redis = $redisFactory->get('default');
        $this->keyPrefix = $keyPrefix;
    }

    public function store(string $shopId, array $tokenData): bool
    {
        $key = $this->keyPrefix . $shopId;
        $data = json_encode($tokenData, JSON_UNESCAPED_UNICODE);
        
        // 设置过期时间比实际令牌过期时间长一些，用于刷新令牌
        $ttl = $tokenData['expires_in'] ?? 7200;
        $ttl += 86400; // 多保存1天用于刷新
        
        $result = $this->redis->setex($key, $ttl, $data);
        
        // 同时维护一个商家列表
        $this->redis->sadd($this->keyPrefix . 'shops', $shopId);
        
        return $result !== false;
    }

    public function get(string $shopId): ?array
    {
        $key = $this->keyPrefix . $shopId;
        $data = $this->redis->get($key);
        
        if ($data === false) {
            return null;
        }
        
        $tokenData = json_decode($data, true);
        return is_array($tokenData) ? $tokenData : null;
    }

    public function delete(string $shopId): bool
    {
        $key = $this->keyPrefix . $shopId;
        $result = $this->redis->del($key);
        
        // 从商家列表中移除
        $this->redis->srem($this->keyPrefix . 'shops', $shopId);
        
        return $result > 0;
    }

    public function list(): array
    {
        $shopIds = $this->redis->smembers($this->keyPrefix . 'shops');
        $shops = [];
        
        foreach ($shopIds as $shopId) {
            $tokenData = $this->get($shopId);
            if ($tokenData) {
                $shops[$shopId] = [
                    'shop_id' => $shopId,
                    'shop_name' => $tokenData['shop_name'] ?? '',
                    'scope' => $tokenData['scope'] ?? '',
                    'authorized_at' => $tokenData['created_at'] ?? 0,
                    'expires_at' => $tokenData['expires_at'] ?? 0,
                    'is_expired' => ($tokenData['expires_at'] ?? 0) <= time(),
                ];
            }
        }
        
        return $shops;
    }

    public function exists(string $shopId): bool
    {
        $key = $this->keyPrefix . $shopId;
        return $this->redis->exists($key) > 0;
    }
} 