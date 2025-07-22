<?php

declare(strict_types=1);

namespace Doudian\Storage;

use Doudian\Core\Contract\TokenStorageInterface;

/**
 * 回调式 Token 存储
 * 允许用户使用自己的存储系统，通过回调函数来处理 token 存储操作
 */
class CallbackTokenStorage implements TokenStorageInterface
{
    protected $storeCallback;
    protected $getCallback;
    protected $deleteCallback;
    protected $listCallback;
    protected $existsCallback;

    public function __construct(
        callable $storeCallback,
        callable $getCallback,
        callable $deleteCallback,
        callable $listCallback = null,
        callable $existsCallback = null
    ) {
        $this->storeCallback = $storeCallback;
        $this->getCallback = $getCallback;
        $this->deleteCallback = $deleteCallback;
        $this->listCallback = $listCallback;
        $this->existsCallback = $existsCallback;
    }

    /**
     * 存储访问令牌信息
     * 
     * @param string $shopId 商家ID
     * @param array $tokenData 令牌数据
     * @return bool
     */
    public function store(int $shopId, array $tokenData, string $clientName = 'default'): bool
    {
        return call_user_func($this->storeCallback, $shopId, $tokenData);
    }

    /**
     * 获取访问令牌信息
     * 
     * @param string $shopId 商家ID
     * @return array|null 返回令牌数据或 null
     */
    public function get(int $shopId, string $clientName = 'default'): ?array
    {
        return call_user_func($this->getCallback, $shopId);
    }

    /**
     * 删除访问令牌信息
     * 
     * @param int $shopId 商家ID
     * @return bool
     */
    public function delete(int $shopId, string $clientName = 'default'): bool
    {
        return call_user_func($this->deleteCallback, $shopId);
    }

    /**
     * 获取所有已授权的商家列表
     * 
     * @return array 商家列表，key 为 shop_id，value 为商家信息
     */
    public function list(string $clientName = 'default'): array
    {
        if ($this->listCallback) {
            return call_user_func($this->listCallback);
        }
        
        // 如果没有提供列表回调，返回空数组
        return [];
    }

    /**
     * 检查商家是否已授权
     * 
     * @param int $shopId 商家ID
     * @return bool
     */
    public function exists(int $shopId, string $clientName = 'default'): bool
    {
        if ($this->existsCallback) {
            return call_user_func($this->existsCallback, $shopId);
        }
        
        // 默认通过 get 方法来判断
        return $this->get($shopId) !== null;
    }
} 