# 抖店工具型应用开发指南

本指南详细介绍如何使用抖店 Hyperf SDK 开发工具型应用，处理多商家授权和 API 调用。

## 🆚 工具型应用 vs 自用型应用

| 特性 | 自用型应用 | 工具型应用 |
|------|------------|------------|
| **授权方式** | 直接使用 shop_id | OAuth 授权码流程 |
| **商家数量** | 单个商家 | 多个商家 |
| **令牌管理** | 简单 | 复杂（需要存储和刷新） |
| **应用场景** | 商家自用 | SaaS 服务提供商 |

## 🚀 快速开始

### 1. 基础配置

在 `config/autoload/doudian.php` 中配置工具型应用：

```php
<?php

return [
    'default' => [
        'app_key' => 'your_tool_app_key',        // 工具型应用 Key
        'app_secret' => 'your_tool_app_secret',  // 工具型应用密钥
        'open_request_url' => 'https://openapi-fxg.jinritemai.com',
        'http_connect_timeout' => 3,
        'http_read_timeout' => 10,
    ],
];
```

### 2. 配置令牌存储

在 `config/autoload/dependencies.php` 中注册令牌存储：

```php
<?php

return [
    // 使用 Redis 存储（推荐）
    \Doudian\Core\Contract\TokenStorageInterface::class => \Doudian\Storage\RedisTokenStorage::class,
    
    // 或者使用数据库存储
    // \Doudian\Core\Contract\TokenStorageInterface::class => \Doudian\Storage\DatabaseTokenStorage::class,
];
```

### 3. 注册工具应用管理器

在 `config/autoload/dependencies.php` 中添加：

```php
\Doudian\Core\ToolAppManager::class => \Doudian\Core\ToolAppManager::class,
```

## 🔐 授权流程实现

### 1. 生成授权链接

```php
<?php

namespace App\Controller;

use Doudian\Core\ToolAppManager;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\Di\Annotation\Inject;

#[Controller(prefix: '/doudian')]
class AuthController
{
    #[Inject]
    private ToolAppManager $toolAppManager;

    #[GetMapping('/auth')]
    public function getAuthUrl(): array
    {
        $redirectUri = 'https://your-domain.com/doudian/callback';
        $state = 'unique_state_' . time();
        
        $authUrl = $this->toolAppManager->generateAuthUrl($redirectUri, $state);
        
        // 保存 state 到 session 或 Redis，用于后续验证
        // $this->saveState($state);
        
        return [
            'auth_url' => $authUrl,
            'state' => $state,
        ];
    }
}
```

### 2. 处理授权回调

```php
#[PostMapping('/callback')]
public function handleCallback(RequestInterface $request): array
{
    $code = $request->input('code');
    $state = $request->input('state');
    
    // 验证 state 参数
    // if (!$this->verifyState($state)) {
    //     return ['error' => 'Invalid state'];
    // }
    
    try {
        $accessToken = $this->toolAppManager->handleAuthCallback($code);
        
        if ($accessToken->isSuccess()) {
            return [
                'success' => true,
                'shop_id' => $accessToken->getShopId(),
                'shop_name' => $accessToken->getShopName(),
                'scope' => $accessToken->getScope(),
            ];
        } else {
            return [
                'success' => false,
                'error' => $accessToken->getMsg(),
            ];
        }
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}
```

## 🏪 多商家管理

### 1. 获取授权商家列表

```php
#[GetMapping('/shops')]
public function getAuthorizedShops(): array
{
    $shops = $this->toolAppManager->getAuthorizedShops();
    
    return [
        'shops' => $shops,
        'count' => count($shops),
    ];
}
```

### 2. 检查商家授权状态

```php
#[GetMapping('/shop/{shopId}/status')]
public function checkShopStatus(string $shopId): array
{
    $shopInfo = $this->toolAppManager->getShopInfo($shopId);
    $accessToken = $this->toolAppManager->getShopAccessToken($shopId);
    
    return [
        'shop_id' => $shopId,
        'is_authorized' => $accessToken !== null,
        'is_expired' => $shopInfo ? $shopInfo['is_expired'] : true,
        'shop_info' => $shopInfo,
    ];
}
```

### 3. 代理商家调用 API

```php
#[GetMapping('/shop/{shopId}/products')]
public function getShopProducts(string $shopId, RequestInterface $request): array
{
    // 获取商家访问令牌
    $accessToken = $this->toolAppManager->getShopAccessToken($shopId);
    
    if (!$accessToken) {
        return [
            'error' => '商家未授权或令牌已过期',
            'need_reauth' => true,
        ];
    }
    
    // 调用 API
    $client = $this->clientFactory->create();
    
    $productRequest = new ProductListRequest();
    $param = new ProductListParam();
    $param->page = (int) $request->input('page', 0);
    $param->size = (int) $request->input('size', 20);
    $productRequest->setParam($param);
    
    try {
        $response = $client->request($productRequest, $accessToken);
        
        return [
            'success' => true,
            'shop_id' => $shopId,
            'data' => $response,
        ];
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}
```

## 🔄 令牌管理

### 1. 自动刷新令牌

```php
#[PostMapping('/shop/{shopId}/refresh')]
public function refreshToken(string $shopId): array
{
    try {
        $newAccessToken = $this->toolAppManager->refreshShopAccessToken($shopId);
        
        if ($newAccessToken) {
            return [
                'success' => true,
                'message' => '令牌刷新成功',
                'expires_in' => $newAccessToken->getExpireIn(),
            ];
        } else {
            return [
                'success' => false,
                'message' => '令牌刷新失败，需要重新授权',
                'need_reauth' => true,
            ];
        }
    } catch (\Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
        ];
    }
}
```

### 2. 定时任务刷新令牌

```php
<?php

namespace App\Crontab;

use Doudian\Core\ToolAppManager;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

class TokenRefreshCrontab
{
    #[Inject]
    private ToolAppManager $toolAppManager;

    #[Crontab(rule: "0 */2 * * *", memo: "每2小时检查并刷新即将过期的令牌")]
    public function refreshTokens(): void
    {
        $shops = $this->toolAppManager->getAuthorizedShops();
        
        foreach ($shops as $shopId => $shopInfo) {
            // 提前30分钟刷新令牌
            if ($shopInfo['expires_at'] <= time() + 1800) {
                try {
                    $this->toolAppManager->refreshShopAccessToken($shopId);
                    echo "刷新商家 {$shopId} 令牌成功\n";
                } catch (\Exception $e) {
                    echo "刷新商家 {$shopId} 令牌失败: " . $e->getMessage() . "\n";
                    // 可以发送通知或记录日志
                }
            }
        }
    }
}
```

## 🚀 高性能批量操作

### 1. 协程并发处理

```php
use Hyperf\Coroutine\Parallel;

#[GetMapping('/batch/products')]
public function batchGetProducts(): array
{
    $shops = $this->toolAppManager->getAuthorizedShops();
    $parallel = new Parallel();
    
    foreach ($shops as $shopId => $shopInfo) {
        if (!$shopInfo['is_expired']) {
            $parallel->add(function () use ($shopId) {
                return $this->getShopProductsData($shopId);
            });
        }
    }
    
    $results = $parallel->wait();
    
    return [
        'total_shops' => count($results),
        'results' => $results,
    ];
}

private function getShopProductsData(string $shopId): array
{
    try {
        $accessToken = $this->toolAppManager->getShopAccessToken($shopId);
        if (!$accessToken) {
            return ['shop_id' => $shopId, 'error' => '无访问令牌'];
        }
        
        $client = $this->clientFactory->create();
        $request = new ProductListRequest();
        $param = new ProductListParam();
        $param->page = 0;
        $param->size = 10;
        $request->setParam($param);
        
        $response = $client->request($request, $accessToken);
        
        return [
            'shop_id' => $shopId,
            'success' => true,
            'product_count' => property_exists($response, 'data') ? count($response->data) : 0,
        ];
    } catch (\Exception $e) {
        return [
            'shop_id' => $shopId,
            'error' => $e->getMessage(),
        ];
    }
}
```

### 2. 连接池优化

配置连接池以提高并发性能：

```php
// config/autoload/doudian.php
return [
    'default' => [
        'app_key' => 'your_app_key',
        'app_secret' => 'your_app_secret',
        'pool' => [
            'min_connections' => 10,    // 最小连接数
            'max_connections' => 50,    // 最大连接数
            'connect_timeout' => 10.0,  // 连接超时
            'wait_timeout' => 3.0,      // 等待超时
            'heartbeat' => -1,          // 心跳间隔
            'max_idle_time' => 60.0,    // 最大空闲时间
        ],
    ],
];
```

## 🛡️ 安全最佳实践

### 1. 签名验证

```php
public function verifyCallback(RequestInterface $request): bool
{
    $params = $request->all();
    return $this->toolAppManager->verifyCallbackSignature($params);
}
```

### 2. 状态参数验证

```php
private function generateState(): string
{
    $state = bin2hex(random_bytes(32));
    // 存储到 Redis 或数据库，设置过期时间
    $this->redis->setex('auth_state:' . $state, 600, time());
    return $state;
}

private function verifyState(string $state): bool
{
    return $this->redis->exists('auth_state:' . $state);
}
```

### 3. 访问令牌加密存储

```php
// 在存储前加密令牌
private function encryptToken(string $token): string
{
    return openssl_encrypt($token, 'AES-256-CBC', $this->getEncryptKey(), 0, $this->getIv());
}

private function decryptToken(string $encryptedToken): string
{
    return openssl_decrypt($encryptedToken, 'AES-256-CBC', $this->getEncryptKey(), 0, $this->getIv());
}
```

## 📊 监控和日志

### 1. API 调用监控

```php
use Hyperf\Logger\LoggerFactory;

#[Inject]
private LoggerInterface $logger;

private function logApiCall(string $shopId, string $api, bool $success, ?string $error = null): void
{
    $this->logger->info('Tool App API Call', [
        'shop_id' => $shopId,
        'api' => $api,
        'success' => $success,
        'error' => $error,
        'timestamp' => time(),
    ]);
}
```

### 2. 令牌状态监控

```php
#[GetMapping('/monitor/tokens')]
public function monitorTokens(): array
{
    $shops = $this->toolAppManager->getAuthorizedShops();
    $stats = [
        'total' => count($shops),
        'active' => 0,
        'expired' => 0,
        'expiring_soon' => 0, // 24小时内过期
    ];
    
    foreach ($shops as $shopInfo) {
        if ($shopInfo['is_expired']) {
            $stats['expired']++;
        } elseif ($shopInfo['expires_at'] <= time() + 86400) {
            $stats['expiring_soon']++;
        } else {
            $stats['active']++;
        }
    }
    
    return $stats;
}
```

## 🎯 完整应用示例

这是一个完整的工具型应用控制器示例：

```php
<?php

namespace App\Controller;

use Doudian\Core\ToolAppManager;
use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Api\Product\ProductListRequest;
use Doudian\Api\Product\Param\ProductListParam;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Di\Annotation\Inject;

#[Controller(prefix: '/saas/doudian')]
class SaasDoudianController
{
    #[Inject]
    private ToolAppManager $toolAppManager;
    
    #[Inject]
    private ClientFactoryInterface $clientFactory;

    // 商家授权入口
    #[GetMapping('/auth')]
    public function startAuth(): array
    {
        $redirectUri = 'https://your-saas.com/saas/doudian/callback';
        $authUrl = $this->toolAppManager->generateAuthUrl($redirectUri);
        
        return ['auth_url' => $authUrl];
    }

    // 处理授权回调
    #[PostMapping('/callback')]
    public function handleCallback(RequestInterface $request): array
    {
        $code = $request->input('code');
        $accessToken = $this->toolAppManager->handleAuthCallback($code);
        
        return [
            'success' => $accessToken->isSuccess(),
            'shop_id' => $accessToken->getShopId(),
            'shop_name' => $accessToken->getShopName(),
        ];
    }

    // 获取商家商品数据
    #[GetMapping('/shop/{shopId}/products')]
    public function getProducts(string $shopId, RequestInterface $request): array
    {
        $accessToken = $this->toolAppManager->getShopAccessToken($shopId);
        if (!$accessToken) {
            return ['error' => '商家未授权'];
        }

        $client = $this->clientFactory->create();
        $productRequest = new ProductListRequest();
        $param = new ProductListParam();
        $param->page = (int) $request->input('page', 0);
        $param->size = (int) $request->input('size', 20);
        $productRequest->setParam($param);

        $response = $client->request($productRequest, $accessToken);
        
        return ['data' => $response];
    }

    // 管理面板 - 查看所有授权商家
    #[GetMapping('/admin/shops')]
    public function adminShops(): array
    {
        return $this->toolAppManager->getAuthorizedShops();
    }
}
```

通过这个完整的工具型应用方案，您可以构建强大的 SaaS 服务，为多个抖店商家提供统一的管理和服务平台！ 