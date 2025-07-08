# Hyperf 框架中调试抖店 SDK 指南

本指南将详细介绍如何在现有的 Hyperf 项目中集成、使用和调试抖店 SDK。

## 🚀 集成到现有 Hyperf 项目

### 方法一：本地开发调试（推荐用于开发阶段）

在您的 Hyperf 项目根目录中，修改 `composer.json`：

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/Users/lzq/Downloads/sdk-php/src"
        }
    ],
    "require": {
        "doudian/hyperf-sdk": "@dev"
    }
}
```

然后执行：
```bash
composer update doudian/hyperf-sdk
```

### 方法二：从 Git 仓库安装

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/Saym-l/doudian-hyperf-sdk.git"
        }
    ],
    "require": {
        "doudian/hyperf-sdk": "dev-main"
    }
}
```

### 方法三：发布到 Packagist 后安装

```bash
composer require doudian/hyperf-sdk
```

## ⚙️ 配置 SDK

### 1. 发布配置文件

```bash
php bin/hyperf.php vendor:publish doudian/hyperf-sdk
```

### 2. 编辑配置文件

编辑 `config/autoload/doudian.php`：

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
];
```

### 3. 环境变量配置

在 `.env` 文件中添加：

```env
DOUDIAN_APP_KEY=your_app_key
DOUDIAN_APP_SECRET=your_app_secret
DOUDIAN_OPEN_REQUEST_URL=https://openapi-fxg.jinritemai.com
DOUDIAN_HTTP_CONNECT_TIMEOUT=3
DOUDIAN_HTTP_READ_TIMEOUT=10
```

## 🐛 调试方法

### 1. 启用调试日志

编辑 `config/autoload/logger.php`：

```php
<?php

return [
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\StreamHandler::class,
                'constructor' => [
                    'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
                    'level' => Monolog\Logger::DEBUG,
                ],
            ],
        ],
    ],
    // 添加抖店 SDK 专用日志
    'doudian' => [
        'handler' => [
            'class' => Monolog\Handler\StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/runtime/logs/doudian.log',
                'level' => Monolog\Logger::DEBUG,
            ],
        ],
        'formatter' => [
            'class' => Monolog\Formatter\LineFormatter::class,
            'constructor' => [
                'format' => null,
                'dateFormat' => 'Y-m-d H:i:s',
                'allowInlineLineBreaks' => true,
            ],
        ],
    ],
    // HTTP 请求日志
    'guzzle' => [
        'handler' => [
            'class' => Monolog\Handler\StreamHandler::class,
            'constructor' => [
                'stream' => BASE_PATH . '/runtime/logs/guzzle.log',
                'level' => Monolog\Logger::DEBUG,
            ],
        ],
    ],
];
```

### 2. 创建调试控制器

创建 `app/Controller/DoudianDebugController.php`：

```php
<?php

namespace App\Controller;

use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\AccessTokenBuilder;
use Doudian\Api\Product\ProductListRequest;
use Doudian\Api\Product\Param\ProductListParam;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Di\Annotation\Inject;
use Psr\Log\LoggerInterface;

#[Controller(prefix: '/debug/doudian')]
class DoudianDebugController
{
    #[Inject]
    private ClientFactoryInterface $doudianClientFactory;

    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('doudian');
    }

    #[GetMapping('/test-config')]
    public function testConfig(): array
    {
        try {
            $client = $this->doudianClientFactory->create();
            $config = $client->getConfig();
            
            $this->logger->info('测试配置', [
                'app_key' => $config->getAppKey(),
                'app_secret' => substr($config->getAppSecret(), 0, 10) . '***',
                'open_request_url' => $config->getOpenRequestUrl(),
                'connect_timeout' => $config->getHttpConnectTimeout(),
                'read_timeout' => $config->getHttpReadTimeout(),
            ]);

            return [
                'success' => true,
                'message' => '配置加载成功',
                'data' => [
                    'app_key' => $config->getAppKey(),
                    'app_secret_preview' => substr($config->getAppSecret(), 0, 10) . '***',
                    'open_request_url' => $config->getOpenRequestUrl(),
                    'timeouts' => [
                        'connect' => $config->getHttpConnectTimeout(),
                        'read' => $config->getHttpReadTimeout(),
                    ],
                    'pool_config' => $config->getPoolConfig(),
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('配置测试失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '配置测试失败: ' . $e->getMessage(),
            ];
        }
    }

    #[GetMapping('/test-token')]
    public function testToken(): array
    {
        try {
            // 注意：这里需要替换为真实的授权码或 shop_id
            $authCode = 'your_test_auth_code';
            
            $this->logger->info('开始测试访问令牌获取', ['auth_code' => $authCode]);
            
            $accessToken = AccessTokenBuilder::build($authCode, AccessTokenBuilder::ACCESS_TOKEN_CODE);
            
            if ($accessToken->isSuccess()) {
                $this->logger->info('访问令牌获取成功', [
                    'access_token_preview' => substr($accessToken->getAccessToken() ?? '', 0, 20) . '***',
                    'expires_in' => $accessToken->getExpireIn(),
                    'shop_id' => $accessToken->getShopId(),
                ]);

                return [
                    'success' => true,
                    'message' => '访问令牌获取成功',
                    'data' => [
                        'access_token_preview' => substr($accessToken->getAccessToken() ?? '', 0, 20) . '***',
                        'expires_in' => $accessToken->getExpireIn(),
                        'refresh_token_preview' => substr($accessToken->getRefreshToken() ?? '', 0, 20) . '***',
                        'shop_id' => $accessToken->getShopId(),
                        'shop_name' => $accessToken->getShopName(),
                    ]
                ];
            } else {
                $this->logger->error('访问令牌获取失败', [
                    'code' => $accessToken->getCode(),
                    'message' => $accessToken->getMsg(),
                    'sub_code' => $accessToken->getSubCode(),
                    'sub_msg' => $accessToken->getSubMsg(),
                ]);

                return [
                    'success' => false,
                    'message' => '访问令牌获取失败',
                    'error' => [
                        'code' => $accessToken->getCode(),
                        'message' => $accessToken->getMsg(),
                        'sub_code' => $accessToken->getSubCode(),
                        'sub_msg' => $accessToken->getSubMsg(),
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('访问令牌测试异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '访问令牌测试异常: ' . $e->getMessage(),
            ];
        }
    }

    #[GetMapping('/test-api')]
    public function testApi(): array
    {
        try {
            $client = $this->doudianClientFactory->create();
            
            // 使用已有的访问令牌进行测试
            $accessTokenStr = 'your_existing_access_token';
            $accessToken = AccessTokenBuilder::parse($accessTokenStr);
            
            $this->logger->info('开始测试 API 调用');

            // 测试产品列表 API
            $request = new ProductListRequest();
            $param = new ProductListParam();
            $param->page = 0;
            $param->size = 5;
            $request->setParam($param);

            $this->logger->info('发起产品列表请求', [
                'url_path' => $request->getUrlPath(),
                'params' => [
                    'page' => $param->page,
                    'size' => $param->size,
                ]
            ]);

            $response = $client->request($request, $accessToken);

            $this->logger->info('API 调用完成', [
                'response_preview' => json_encode($response, JSON_UNESCAPED_UNICODE)
            ]);

            return [
                'success' => true,
                'message' => 'API 调用成功',
                'data' => $response
            ];

        } catch (\Exception $e) {
            $this->logger->error('API 调用异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'API 调用异常: ' . $e->getMessage(),
            ];
        }
    }

    #[GetMapping('/test-multiple-clients')]
    public function testMultipleClients(): array
    {
        try {
            // 测试多客户端支持
            $defaultClient = $this->doudianClientFactory->create();
            $shop1Client = $this->doudianClientFactory->get('shop1');

            $this->logger->info('测试多客户端配置', [
                'default_config' => [
                    'app_key' => $defaultClient->getConfig()->getAppKey(),
                ],
                'shop1_config' => [
                    'app_key' => $shop1Client->getConfig()->getAppKey(),
                ],
            ]);

            return [
                'success' => true,
                'message' => '多客户端测试完成',
                'data' => [
                    'default_client' => [
                        'app_key' => $defaultClient->getConfig()->getAppKey(),
                        'url' => $defaultClient->getConfig()->getOpenRequestUrl(),
                    ],
                    'shop1_client' => [
                        'app_key' => $shop1Client->getConfig()->getAppKey(),
                        'url' => $shop1Client->getConfig()->getOpenRequestUrl(),
                    ],
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('多客户端测试失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '多客户端测试失败: ' . $e->getMessage(),
            ];
        }
    }
}
```

### 3. 创建调试服务类

创建 `app/Service/DoudianDebugService.php`：

```php
<?php

namespace App\Service;

use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\AccessToken;
use Doudian\Api\Product\ProductListRequest;
use Doudian\Api\Product\Param\ProductListParam;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use Hyperf\Coroutine\Parallel;

class DoudianDebugService
{
    #[Inject]
    private ClientFactoryInterface $clientFactory;

    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('doudian');
    }

    /**
     * 测试协程并发请求
     */
    public function testConcurrentRequests(AccessToken $accessToken, int $concurrency = 5): array
    {
        $this->logger->info("开始测试协程并发请求", ['concurrency' => $concurrency]);
        
        $parallel = new Parallel();
        $startTime = microtime(true);

        for ($i = 0; $i < $concurrency; $i++) {
            $parallel->add(function () use ($accessToken, $i) {
                $client = $this->clientFactory->create();
                
                $request = new ProductListRequest();
                $param = new ProductListParam();
                $param->page = $i;
                $param->size = 10;
                $request->setParam($param);

                $requestStart = microtime(true);
                $response = $client->request($request, $accessToken);
                $requestTime = (microtime(true) - $requestStart) * 1000;

                $this->logger->info("协程请求完成", [
                    'request_id' => $i,
                    'request_time_ms' => round($requestTime, 2),
                ]);

                return [
                    'request_id' => $i,
                    'request_time_ms' => round($requestTime, 2),
                    'response' => $response,
                ];
            });
        }

        $results = $parallel->wait();
        $totalTime = (microtime(true) - $startTime) * 1000;

        $this->logger->info("协程并发测试完成", [
            'total_requests' => $concurrency,
            'total_time_ms' => round($totalTime, 2),
            'avg_time_ms' => round($totalTime / $concurrency, 2),
        ]);

        return [
            'total_requests' => $concurrency,
            'total_time_ms' => round($totalTime, 2),
            'avg_time_ms' => round($totalTime / $concurrency, 2),
            'results' => $results,
        ];
    }

    /**
     * 性能基准测试
     */
    public function benchmarkPerformance(AccessToken $accessToken, int $iterations = 10): array
    {
        $client = $this->clientFactory->create();
        $times = [];

        $this->logger->info("开始性能基准测试", ['iterations' => $iterations]);

        for ($i = 0; $i < $iterations; $i++) {
            $request = new ProductListRequest();
            $param = new ProductListParam();
            $param->page = 0;
            $param->size = 10;
            $request->setParam($param);

            $start = microtime(true);
            $response = $client->request($request, $accessToken);
            $time = (microtime(true) - $start) * 1000;
            
            $times[] = $time;
            
            $this->logger->debug("基准测试迭代", [
                'iteration' => $i + 1,
                'time_ms' => round($time, 2),
            ]);
        }

        $avgTime = array_sum($times) / count($times);
        $minTime = min($times);
        $maxTime = max($times);

        $result = [
            'iterations' => $iterations,
            'avg_time_ms' => round($avgTime, 2),
            'min_time_ms' => round($minTime, 2),
            'max_time_ms' => round($maxTime, 2),
            'all_times' => array_map(fn($t) => round($t, 2), $times),
        ];

        $this->logger->info("性能基准测试完成", $result);

        return $result;
    }
}
```

## 🔍 调试技巧

### 1. 使用 Hyperf 调试工具

安装调试工具：
```bash
composer require --dev hyperf/testing
composer require --dev swoole/ide-helper
```

### 2. 启用 SQL 查询日志（如果 SDK 涉及数据库）

```php
// config/autoload/databases.php
'default' => [
    'driver' => env('DB_DRIVER', 'mysql'),
    'host' => env('DB_HOST', 'localhost'),
    // ...
    'options' => [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
],
```

### 3. 使用 Swoole Tracker（可选）

```bash
# 安装 Swoole Tracker
composer require swoole/tracker
```

### 4. 断点调试配置

在 PhpStorm 中配置 Xdebug：

```ini
# php.ini
[xdebug]
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
```

## 📊 监控和性能分析

### 1. 添加性能监控

创建中间件 `app/Middleware/DoudianPerformanceMiddleware.php`：

```php
<?php

namespace App\Middleware;

use Hyperf\Logger\LoggerFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;

class DoudianPerformanceMiddleware implements MiddlewareInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('doudian');
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $start = microtime(true);
        
        $response = $handler->handle($request);
        
        $duration = (microtime(true) - $start) * 1000;
        
        if (str_contains($request->getUri()->getPath(), '/debug/doudian')) {
            $this->logger->info('DoudianSDK 请求性能', [
                'path' => $request->getUri()->getPath(),
                'method' => $request->getMethod(),
                'duration_ms' => round($duration, 2),
                'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            ]);
        }
        
        return $response;
    }
}
```

### 2. 注册中间件

在 `config/autoload/middlewares.php` 中：

```php
<?php

return [
    'http' => [
        App\Middleware\DoudianPerformanceMiddleware::class,
    ],
];
```

## 🧪 单元测试

创建测试文件 `test/DoudianSdkTest.php`：

```php
<?php

namespace HyperfTest;

use Doudian\Core\Config;
use Doudian\Core\AccessTokenBuilder;
use Hyperf\Testing\TestCase;
use Hyperf\Context\ApplicationContext;

class DoudianSdkTest extends TestCase
{
    public function testConfigLoading()
    {
        $container = ApplicationContext::getContainer();
        $config = new Config($container, 'default');
        
        $this->assertNotEmpty($config->getAppKey());
        $this->assertNotEmpty($config->getAppSecret());
    }

    public function testAccessTokenParsing()
    {
        $tokenStr = 'test_access_token';
        $accessToken = AccessTokenBuilder::parse($tokenStr);
        
        $this->assertEquals($tokenStr, $accessToken->getAccessToken());
    }
}
```

运行测试：
```bash
composer test
```

## 🚀 实际使用示例

访问以下 URL 进行调试：

1. **配置测试**: `GET /debug/doudian/test-config`
2. **令牌测试**: `GET /debug/doudian/test-token`
3. **API 测试**: `GET /debug/doudian/test-api`
4. **多客户端测试**: `GET /debug/doudian/test-multiple-clients`

## 📝 日志查看

```bash
# 查看抖店 SDK 日志
tail -f runtime/logs/doudian.log

# 查看 HTTP 请求日志
tail -f runtime/logs/guzzle.log

# 查看 Hyperf 主日志
tail -f runtime/logs/hyperf.log
```

## ❓ 常见调试问题

1. **配置未加载**: 检查配置文件路径和环境变量
2. **依赖注入失败**: 确保 ConfigProvider 已注册
3. **HTTP 请求超时**: 调整超时配置
4. **协程上下文丢失**: 检查协程安全性

通过以上方法，您可以全面调试和监控抖店 SDK 在 Hyperf 项目中的运行情况。 