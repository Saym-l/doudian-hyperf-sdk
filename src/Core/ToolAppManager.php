<?php

declare(strict_types=1);

namespace Doudian\Core;

use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\Contract\TokenStorageInterface;
use Doudian\Utils\SignUtil;
use Psr\Container\ContainerInterface;

class ToolAppManager
{
    protected ContainerInterface $container;
    protected ClientFactoryInterface $clientFactory;
    protected ?TokenStorageInterface $tokenStorage = null;

    public function __construct(ContainerInterface $container, ClientFactoryInterface $clientFactory)
    {
        $this->container = $container;
        $this->clientFactory = $clientFactory;
        
        if ($container->has(TokenStorageInterface::class)) {
            $this->tokenStorage = $container->get(TokenStorageInterface::class);
        }
    }

    /**
     * 生成授权链接
     */
    public function generateAuthUrl(string $redirectUri, string $state = '', string $clientName = 'default'): string
    {
        $client = $this->clientFactory->get($clientName);
        $config = $client->getConfig();
        
        $params = [
            'app_key' => $config->getAppKey(),
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'state' => $state ?: $this->generateState(),
            'scope' => 'trade_basic,product_basic', // 根据需要调整权限范围
        ];

        $queryString = http_build_query($params);
        return 'https://openapi-fxg.jinritemai.com/oauth/authorize?' . $queryString;
    }

    /**
     * 处理授权回调，获取访问令牌
     */
    public function handleAuthCallback(string $code, string $shopId = '', string $clientName = 'default'): AccessToken
    {
        $accessToken = AccessTokenBuilder::build($code, AccessTokenBuilder::ACCESS_TOKEN_CODE, $clientName);
        
        if ($accessToken->isSuccess() && $this->tokenStorage) {
            // 存储令牌信息
            $this->tokenStorage->store($shopId ?: $accessToken->getShopId(), [
                'access_token' => $accessToken->getAccessToken(),
                'refresh_token' => $accessToken->getRefreshToken(),
                'expires_in' => $accessToken->getExpireIn(),
                'expires_at' => time() + $accessToken->getExpireIn(),
                'shop_id' => $accessToken->getShopId(),
                'shop_name' => $accessToken->getShopName(),
                'scope' => $accessToken->getScope(),
                'created_at' => time(),
            ]);
        }
        
        return $accessToken;
    }

    /**
     * 获取商家的有效访问令牌
     */
    public function getShopAccessToken(string $shopId): ?AccessToken
    {
        if (!$this->tokenStorage) {
            throw new \RuntimeException('TokenStorage not configured');
        }

        $tokenData = $this->tokenStorage->get($shopId);
        if (!$tokenData) {
            return null;
        }

        // 检查令牌是否过期
        if ($tokenData['expires_at'] <= time() + 300) { // 提前5分钟刷新
            return $this->refreshShopAccessToken($shopId);
        }

        return AccessTokenBuilder::parse($tokenData['access_token']);
    }

    /**
     * 刷新商家访问令牌
     */
    public function refreshShopAccessToken(string $shopId, string $clientName = 'default'): ?AccessToken
    {
        if (!$this->tokenStorage) {
            throw new \RuntimeException('TokenStorage not configured');
        }

        $tokenData = $this->tokenStorage->get($shopId);
        if (!$tokenData || !$tokenData['refresh_token']) {
            return null;
        }

        try {
            $newAccessToken = AccessTokenBuilder::refresh($tokenData['refresh_token'], $clientName);
            
            if ($newAccessToken->isSuccess()) {
                // 更新存储的令牌信息
                $this->tokenStorage->store($shopId, [
                    'access_token' => $newAccessToken->getAccessToken(),
                    'refresh_token' => $newAccessToken->getRefreshToken(),
                    'expires_in' => $newAccessToken->getExpireIn(),
                    'expires_at' => time() + $newAccessToken->getExpireIn(),
                    'shop_id' => $newAccessToken->getShopId(),
                    'shop_name' => $newAccessToken->getShopName(),
                    'scope' => $newAccessToken->getScope(),
                    'updated_at' => time(),
                ]);
                
                return $newAccessToken;
            }
        } catch (\Exception $e) {
            // 刷新失败，可能需要重新授权
            $this->tokenStorage->delete($shopId);
        }

        return null;
    }

    /**
     * 撤销商家授权
     */
    public function revokeShopAuth(string $shopId): bool
    {
        if ($this->tokenStorage) {
            return $this->tokenStorage->delete($shopId);
        }
        
        return false;
    }

    /**
     * 获取所有已授权的商家列表
     */
    public function getAuthorizedShops(): array
    {
        if (!$this->tokenStorage) {
            return [];
        }

        return $this->tokenStorage->list();
    }

    /**
     * 验证授权回调的签名（如果需要）
     */
    public function verifyCallbackSignature(array $params, string $clientName = 'default'): bool
    {
        $client = $this->clientFactory->get($clientName);
        $config = $client->getConfig();
        
        if (!isset($params['sign'])) {
            return false;
        }

        $sign = $params['sign'];
        unset($params['sign']);
        
        ksort($params);
        $stringToSign = '';
        foreach ($params as $key => $value) {
            $stringToSign .= $key . $value;
        }
        
        $expectedSign = hash_hmac('sha256', $stringToSign, $config->getAppSecret());
        
        return hash_equals($expectedSign, $sign);
    }

    /**
     * 生成状态参数
     */
    protected function generateState(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * 获取商家信息
     */
    public function getShopInfo(string $shopId): ?array
    {
        if (!$this->tokenStorage) {
            return null;
        }

        $tokenData = $this->tokenStorage->get($shopId);
        if (!$tokenData) {
            return null;
        }

        return [
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