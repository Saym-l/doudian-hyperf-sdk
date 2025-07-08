# 抖店开放平台 PHP SDK for Hyperf

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.0-brightgreen.svg)](https://php.net/)
[![Hyperf Version](https://img.shields.io/badge/hyperf-%5E3.0-blue.svg)](https://hyperf.io/)

这是一个专为 Hyperf 框架优化的抖店开放平台 PHP SDK，支持协程并发、依赖注入、连接池等 Hyperf 特性。

## 特性

- ✅ **协程支持**: 基于 Hyperf Guzzle 的协程 HTTP 客户端
- ✅ **依赖注入**: 完全集成 Hyperf DI 容器
- ✅ **连接池**: 支持 HTTP 连接池，提高性能
- ✅ **多应用配置**: 支持同时管理多个抖店应用
- ✅ **类型安全**: 使用 PHP 8+ 严格类型声明
- ✅ **PSR 标准**: 遵循 PSR-4 自动加载和 PSR-1 编码标准
- ✅ **零依赖冲突**: 与原版 SDK 可共存

## 安装

```bash
composer require doudian/hyperf-sdk
```

## 配置

### 1. 发布配置文件

```bash
php bin/hyperf.php vendor:publish doudian/hyperf-sdk
```

### 2. 编辑配置文件

编辑 `config/autoload/doudian.php`:

```php
<?php

return [
    'default' => [
        'app_key' => env('DOUDIAN_APP_KEY', ''),
        'app_secret' => env('DOUDIAN_APP_SECRET', ''),
        'open_request_url' => env('DOUDIAN_OPEN_REQUEST_URL', 'https://openapi-fxg.jinritemai.com'),
        'http_connect_timeout' => (int) env('DOUDIAN_HTTP_CONNECT_TIMEOUT', 3),
        'http_read_timeout' => (int) env('DOUDIAN_HTTP_READ_TIMEOUT', 10),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 60.0,
        ],
    ],
    // 支持多个应用配置
    'shops' => [
        'shop1' => [
            'app_key' => 'your_app_key_1',
            'app_secret' => 'your_app_secret_1',
            // 其他配置项...
        ],
        'shop2' => [
            'app_key' => 'your_app_key_2',
            'app_secret' => 'your_app_secret_2',
            // 其他配置项...
        ],
    ],
];
```

### 3. 环境变量

在 `.env` 文件中添加：

```env
DOUDIAN_APP_KEY=your_app_key
DOUDIAN_APP_SECRET=your_app_secret
DOUDIAN_OPEN_REQUEST_URL=https://openapi-fxg.jinritemai.com
DOUDIAN_HTTP_CONNECT_TIMEOUT=3
DOUDIAN_HTTP_READ_TIMEOUT=10
```

## 快速开始

### 1. 在控制器中使用

```php
<?php

namespace App\Controller;

use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\AccessTokenBuilder;
use Doudian\Api\Product\ProductListRequest;
use Doudian\Api\Product\Param\ProductListParam;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;

#[Controller]
class DoudianController
{
    public function __construct(
        private ClientFactoryInterface $doudianClientFactory
    ) {}

    #[GetMapping('/doudian/products')]
    public function getProducts(): array
    {
        // 获取客户端（使用默认配置）
        $client = $this->doudianClientFactory->create();
        
        // 或者获取指定应用的客户端
        // $client = $this->doudianClientFactory->get('shop1');
        
        // 构建访问令牌
        $accessToken = AccessTokenBuilder::parse('your_access_token');
        
        // 构建请求
        $request = new ProductListRequest();
        $param = new ProductListParam();
        $param->page = 0;
        $param->size = 20;
        $param->status = 1; // 上架状态
        $request->setParam($param);
        
        // 发起请求
        $response = $client->request($request, $accessToken);
        
        return ['data' => $response];
    }
}
```

### 2. 在服务类中使用

```php
<?php

namespace App\Service;

use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\AccessToken;
use Doudian\Api\Product\ProductListRequest;
use Doudian\Api\Product\Param\ProductListParam;
use Hyperf\Di\Annotation\Inject;

class DoudianService
{
    #[Inject]
    private ClientFactoryInterface $clientFactory;

    public function getProductList(AccessToken $accessToken, int $page = 0, int $size = 10): object
    {
        $client = $this->clientFactory->create();
        
        $request = new ProductListRequest();
        $param = new ProductListParam();
        $param->page = $page;
        $param->size = $size;
        $request->setParam($param);
        
        return $client->request($request, $accessToken);
    }
    
    public function getShop1ProductList(AccessToken $accessToken): object
    {
        // 使用指定的应用配置
        $client = $this->clientFactory->get('shop1');
        
        $request = new ProductListRequest();
        $param = new ProductListParam();
        $param->page = 0;
        $param->size = 100;
        $request->setParam($param);
        
        return $client->request($request, $accessToken);
    }
}
```

## 访问令牌管理

### 获取访问令牌

```php
use Doudian\Core\AccessTokenBuilder;

// 方式 1: 使用授权码获取访问令牌
$accessToken = AccessTokenBuilder::build(
    'your_authorization_code', 
    AccessTokenBuilder::ACCESS_TOKEN_CODE
);

// 方式 2: 使用 shop_id 获取访问令牌（自用型应用）
$accessToken = AccessTokenBuilder::build(
    'your_shop_id', 
    AccessTokenBuilder::ACCESS_TOKEN_SHOP_ID
);

// 方式 3: 使用指定应用配置
$accessToken = AccessTokenBuilder::build(
    'your_authorization_code', 
    AccessTokenBuilder::ACCESS_TOKEN_CODE,
    'shop1'  // 应用配置名称
);

if ($accessToken->isSuccess()) {
    echo "访问令牌: " . $accessToken->getAccessToken();
    echo "有效期: " . $accessToken->getExpireIn() . " 秒";
    echo "刷新令牌: " . $accessToken->getRefreshToken();
} else {
    echo "获取失败: " . $accessToken->getMsg();
}
```

### 刷新访问令牌

```php
// 使用刷新令牌刷新访问令牌
$refreshToken = 'your_refresh_token';
$accessToken = AccessTokenBuilder::refresh($refreshToken);

// 或者使用 AccessToken 对象直接刷新
$accessToken = AccessTokenBuilder::refresh($existingAccessToken);
```

### 解析已有的访问令牌

```php
// 当你已经有访问令牌字符串时
$accessToken = AccessTokenBuilder::parse('your_access_token_string');
```

## API 调用示例

### 产品相关 API

```php
use Doudian\Api\Product\ProductListRequest;
use Doudian\Api\Product\Param\ProductListParam;

// 获取产品列表
$request = new ProductListRequest();
$param = new ProductListParam();
$param->page = 0;
$param->size = 20;
$param->status = 1; // 上架状态
$param->title = '搜索标题'; // 可选
$request->setParam($param);

$response = $client->request($request, $accessToken);
```

### 自定义 API 请求

如果您需要调用尚未封装的 API，可以继承 `AbstractRequest` 类：

```php
<?php

namespace App\Api\Custom;

use Doudian\Core\AbstractRequest;

class CustomApiRequest extends AbstractRequest
{
    public function getUrlPath(): string
    {
        return '/your/custom/api/path';
    }
}

// 使用
$request = new CustomApiRequest();
$param = (object) [
    'your_param' => 'value',
    'another_param' => 123,
];
$request->setParam($param);

$response = $client->request($request, $accessToken);
```

## 错误处理

```php
try {
    $response = $client->request($request, $accessToken);
    
    // 检查业务状态码
    if (property_exists($response, 'code') && $response->code == 10000) {
        // 成功
        $data = $response->data;
    } else {
        // 业务错误
        $errorMsg = $response->message ?? '未知错误';
        throw new \Exception("API 调用失败: $errorMsg");
    }
} catch (\GuzzleHttp\Exception\RequestException $e) {
    // HTTP 请求异常
    throw new \Exception("网络请求失败: " . $e->getMessage());
} catch (\Exception $e) {
    // 其他异常
    throw new \Exception("调用异常: " . $e->getMessage());
}
```

## 配置说明

| 配置项 | 类型 | 默认值 | 说明 |
|--------|------|--------|------|
| `app_key` | string | - | 应用 Key（必填） |
| `app_secret` | string | - | 应用密钥（必填） |
| `open_request_url` | string | https://openapi-fxg.jinritemai.com | API 请求地址 |
| `http_connect_timeout` | int | 3 | 连接超时时间（秒） |
| `http_read_timeout` | int | 10 | 读取超时时间（秒） |
| `pool.min_connections` | int | 1 | 连接池最小连接数 |
| `pool.max_connections` | int | 10 | 连接池最大连接数 |
| `pool.connect_timeout` | float | 10.0 | 连接池连接超时 |
| `pool.wait_timeout` | float | 3.0 | 连接池等待超时 |
| `pool.heartbeat` | int | -1 | 心跳间隔（-1 为禁用） |
| `pool.max_idle_time` | float | 60.0 | 最大空闲时间 |

## 与原版 SDK 的区别

| 特性 | 原版 SDK | Hyperf SDK |
|------|----------|------------|
| HTTP 客户端 | cURL | Hyperf Guzzle (协程) |
| 依赖注入 | 不支持 | 完全支持 |
| 连接池 | 不支持 | 支持 |
| 多应用配置 | 不支持 | 支持 |
| 协程安全 | 否 | 是 |
| 类型声明 | 部分 | 严格类型 |
| PSR 规范 | 部分 | 完全遵循 |

## 性能优化建议

1. **使用连接池**: 在高并发场景下，合理配置连接池参数
2. **缓存访问令牌**: 访问令牌有效期较长，建议缓存避免频繁获取
3. **批量请求**: 利用协程特性并发调用多个 API
4. **错误重试**: 对于网络错误，建议实现重试机制

```php
// 协程并发示例
use Hyperf\Coroutine\Parallel;

$parallel = new Parallel();

$parallel->add(function () use ($client, $accessToken) {
    // 并发请求 1
    $request1 = new ProductListRequest();
    // ... 设置参数
    return $client->request($request1, $accessToken);
});

$parallel->add(function () use ($client, $accessToken) {
    // 并发请求 2
    $request2 = new AnotherRequest();
    // ... 设置参数
    return $client->request($request2, $accessToken);
});

$results = $parallel->wait();
```

## 常见问题

### Q: 如何处理访问令牌过期？

A: 检查响应中的错误码，如果是令牌过期相关错误，使用刷新令牌重新获取：

```php
if (property_exists($response, 'code') && in_array($response->code, [40006, 40007])) {
    // 令牌过期，尝试刷新
    $newAccessToken = AccessTokenBuilder::refresh($refreshToken);
    // 重新发起请求
}
```

### Q: 如何在不同的 Shop 之间切换？

A: 在配置文件中定义多个应用，然后通过名称获取对应的客户端：

```php
$shop1Client = $this->clientFactory->get('shop1');
$shop2Client = $this->clientFactory->get('shop2');
```

### Q: 如何调试 API 请求？

A: 可以在 Hyperf 日志配置中增加 Guzzle 的请求日志：

```php
// config/autoload/logger.php
'guzzle' => [
    'handler' => [
        'class' => \Monolog\Handler\StreamHandler::class,
        'constructor' => [
            'stream' => BASE_PATH . '/runtime/logs/guzzle.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
    ],
],
```

## 许可证

MIT License. 详见 [LICENSE](LICENSE) 文件。

## 贡献

欢迎提交 Issue 和 Pull Request！

## 更新日志

### v1.0.0
- 初始版本发布
- 支持 Hyperf 3.0+ 框架
- 实现协程 HTTP 客户端
- 支持依赖注入和多应用配置 