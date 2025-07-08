<?php

declare(strict_types=1);

/**
 * 抖店工具型应用完整使用示例
 * 
 * 工具型应用是为其他商家提供服务的应用，需要处理多商家授权管理
 */

use Doudian\Core\ToolAppManager;
use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\Contract\TokenStorageInterface;
use Doudian\Storage\RedisTokenStorage;
use Doudian\Storage\DatabaseTokenStorage;
use Doudian\Api\Product\ProductListRequest;
use Doudian\Api\Product\Param\ProductListParam;
use Hyperf\Context\ApplicationContext;

// ===== 1. 工具型应用配置 =====
/*
在 config/autoload/doudian.php 中配置：

return [
    'default' => [
        'app_key' => 'your_tool_app_key',
        'app_secret' => 'your_tool_app_secret',
        'open_request_url' => 'https://openapi-fxg.jinritemai.com',
        'http_connect_timeout' => 3,
        'http_read_timeout' => 10,
    ],
];

在 config/autoload/dependencies.php 中注册令牌存储：

return [
    \Doudian\Core\Contract\TokenStorageInterface::class => \Doudian\Storage\RedisTokenStorage::class,
    // 或者使用数据库存储
    // \Doudian\Core\Contract\TokenStorageInterface::class => \Doudian\Storage\DatabaseTokenStorage::class,
];
*/

// ===== 2. 生成授权链接示例 =====
function generateAuthUrl(): string
{
    $container = ApplicationContext::getContainer();
    $clientFactory = $container->get(ClientFactoryInterface::class);
    $toolAppManager = new ToolAppManager($container, $clientFactory);

    // 设置回调地址
    $redirectUri = 'https://your-domain.com/doudian/tool-app/callback';
    $state = 'unique_state_' . time(); // 用于防止 CSRF 攻击

    $authUrl = $toolAppManager->generateAuthUrl($redirectUri, $state);

    echo "请访问以下链接进行授权：\n";
    echo $authUrl . "\n";
    echo "State: " . $state . "\n";

    return $authUrl;
}

// ===== 3. 处理授权回调示例 =====
function handleAuthCallback(string $code, string $state): void
{
    $container = ApplicationContext::getContainer();
    $clientFactory = $container->get(ClientFactoryInterface::class);
    $toolAppManager = new ToolAppManager($container, $clientFactory);

    try {
        // 验证 state 参数（实际应用中应该从 session 或数据库中验证）
        // if (!verifyState($state)) {
        //     throw new \Exception('Invalid state parameter');
        // }

        $accessToken = $toolAppManager->handleAuthCallback($code);

        if ($accessToken->isSuccess()) {
            echo "授权成功！\n";
            echo "商家ID: " . $accessToken->getShopId() . "\n";
            echo "商家名称: " . $accessToken->getShopName() . "\n";
            echo "授权范围: " . $accessToken->getScope() . "\n";
            echo "有效期: " . $accessToken->getExpireIn() . " 秒\n";
        } else {
            echo "授权失败: " . $accessToken->getMsg() . "\n";
        }
    } catch (\Exception $e) {
        echo "处理授权回调失败: " . $e->getMessage() . "\n";
    }
}

// ===== 4. 获取已授权商家列表 =====
function listAuthorizedShops(): void
{
    $container = ApplicationContext::getContainer();
    $clientFactory = $container->get(ClientFactoryInterface::class);
    $toolAppManager = new ToolAppManager($container, $clientFactory);

    $shops = $toolAppManager->getAuthorizedShops();

    echo "已授权商家列表：\n";
    foreach ($shops as $shopId => $shopInfo) {
        echo sprintf(
            "- 商家ID: %s, 名称: %s, 状态: %s\n",
            $shopInfo['shop_id'],
            $shopInfo['shop_name'],
            $shopInfo['is_expired'] ? '已过期' : '有效'
        );
    }
}

// ===== 5. 代理商家调用 API 示例 =====
function callApiForShop(string $shopId): void
{
    $container = ApplicationContext::getContainer();
    $clientFactory = $container->get(ClientFactoryInterface::class);
    $toolAppManager = new ToolAppManager($container, $clientFactory);

    try {
        // 获取商家的有效访问令牌
        $accessToken = $toolAppManager->getShopAccessToken($shopId);
        
        if (!$accessToken) {
            echo "商家 {$shopId} 未授权或令牌已过期\n";
            return;
        }

        // 创建客户端并调用 API
        $client = $clientFactory->create();
        
        $request = new ProductListRequest();
        $param = new ProductListParam();
        $param->page = 0;
        $param->size = 10;
        $request->setParam($param);

        $response = $client->request($request, $accessToken);

        echo "成功获取商家 {$shopId} 的商品列表：\n";
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

    } catch (\Exception $e) {
        echo "调用 API 失败: " . $e->getMessage() . "\n";
    }
}

// ===== 6. 令牌管理示例 =====
function manageTokens(): void
{
    $container = ApplicationContext::getContainer();
    $clientFactory = $container->get(ClientFactoryInterface::class);
    $toolAppManager = new ToolAppManager($container, $clientFactory);

    // 获取所有商家
    $shops = $toolAppManager->getAuthorizedShops();

    foreach ($shops as $shopId => $shopInfo) {
        echo "检查商家 {$shopId} 的令牌状态...\n";

        if ($shopInfo['is_expired']) {
            echo "令牌已过期，尝试刷新...\n";
            
            $newAccessToken = $toolAppManager->refreshShopAccessToken($shopId);
            
            if ($newAccessToken) {
                echo "令牌刷新成功\n";
            } else {
                echo "令牌刷新失败，需要重新授权\n";
                // 可以发送通知给商家重新授权
                // sendReAuthNotification($shopId);
            }
        } else {
            echo "令牌状态正常\n";
        }
    }
}

// ===== 7. 批量操作示例 =====
function batchOperations(): void
{
    $container = ApplicationContext::getContainer();
    $clientFactory = $container->get(ClientFactoryInterface::class);
    $toolAppManager = new ToolAppManager($container, $clientFactory);

    $shops = $toolAppManager->getAuthorizedShops();
    
    // 使用协程并发处理多个商家的操作
    $parallel = new \Hyperf\Coroutine\Parallel();

    foreach ($shops as $shopId => $shopInfo) {
        if (!$shopInfo['is_expired']) {
            $parallel->add(function () use ($toolAppManager, $clientFactory, $shopId) {
                try {
                    $accessToken = $toolAppManager->getShopAccessToken($shopId);
                    if (!$accessToken) {
                        return ['shop_id' => $shopId, 'error' => '无法获取访问令牌'];
                    }

                    $client = $clientFactory->create();
                    
                    $request = new ProductListRequest();
                    $param = new ProductListParam();
                    $param->page = 0;
                    $param->size = 5;
                    $request->setParam($param);

                    $response = $client->request($request, $accessToken);

                    return [
                        'shop_id' => $shopId,
                        'success' => true,
                        'product_count' => property_exists($response, 'data') ? count($response->data) : 0
                    ];

                } catch (\Exception $e) {
                    return [
                        'shop_id' => $shopId,
                        'error' => $e->getMessage()
                    ];
                }
            });
        }
    }

    $results = $parallel->wait();

    echo "批量操作结果：\n";
    foreach ($results as $result) {
        if (isset($result['error'])) {
            echo "商家 {$result['shop_id']}: 失败 - {$result['error']}\n";
        } else {
            echo "商家 {$result['shop_id']}: 成功 - 商品数量: {$result['product_count']}\n";
        }
    }
}

// ===== 8. 完整的工具型应用控制器示例 =====
/*
将以下代码保存为 app/Controller/DoudianToolAppController.php：

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
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;

#[Controller(prefix: '/doudian/tool-app')]
class DoudianToolAppController
{
    #[Inject]
    private ToolAppManager $toolAppManager;

    #[Inject]
    private ClientFactoryInterface $clientFactory;

    private LoggerInterface $logger;

    public function __construct(LoggerFactory $loggerFactory)
    {
        $this->logger = $loggerFactory->get('doudian');
    }

    // 生成授权链接
    // GET /doudian/tool-app/auth-url?redirect_uri=https://your-domain.com/callback&state=abc123
    #[GetMapping('/auth-url')]
    public function generateAuthUrl(RequestInterface $request): array
    {
        $redirectUri = $request->input('redirect_uri');
        $state = $request->input('state', '');
        $clientName = $request->input('client_name', 'default');

        if (empty($redirectUri)) {
            return [
                'success' => false,
                'message' => '缺少 redirect_uri 参数'
            ];
        }

        try {
            $authUrl = $this->toolAppManager->generateAuthUrl($redirectUri, $state, $clientName);
            
            $this->logger->info('生成授权链接', [
                'redirect_uri' => $redirectUri,
                'state' => $state,
                'auth_url' => $authUrl,
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'auth_url' => $authUrl,
                    'state' => $state,
                    'redirect_uri' => $redirectUri,
                    'instructions' => '请将此链接发送给商家进行授权',
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('生成授权链接失败', [
                'error' => $e->getMessage(),
                'redirect_uri' => $redirectUri,
            ]);
            
            return [
                'success' => false,
                'message' => '生成授权链接失败: ' . $e->getMessage()
            ];
        }
    }

    // 处理授权回调
    // POST /doudian/tool-app/callback
    // Body: {"code": "授权码", "state": "状态参数", "shop_id": "商家ID(可选)"}
    #[PostMapping('/callback')]
    public function handleCallback(RequestInterface $request): array
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $shopId = $request->input('shop_id', '');
        $clientName = $request->input('client_name', 'default');

        if (empty($code)) {
            return [
                'success' => false,
                'message' => '缺少授权码'
            ];
        }

        try {
            $this->logger->info('开始处理授权回调', [
                'code_preview' => substr($code, 0, 10) . '...',
                'state' => $state,
                'shop_id' => $shopId,
            ]);

            $accessToken = $this->toolAppManager->handleAuthCallback($code, $shopId, $clientName);
            
            if ($accessToken->isSuccess()) {
                $this->logger->info('授权成功', [
                    'shop_id' => $accessToken->getShopId(),
                    'shop_name' => $accessToken->getShopName(),
                    'scope' => $accessToken->getScope(),
                ]);

                return [
                    'success' => true,
                    'message' => '授权成功',
                    'data' => [
                        'shop_id' => $accessToken->getShopId(),
                        'shop_name' => $accessToken->getShopName(),
                        'scope' => $accessToken->getScope(),
                        'expires_in' => $accessToken->getExpireIn(),
                        'authorized_at' => date('Y-m-d H:i:s'),
                    ]
                ];
            } else {
                $this->logger->error('授权失败', [
                    'code' => $accessToken->getCode(),
                    'message' => $accessToken->getMsg(),
                    'sub_code' => $accessToken->getSubCode(),
                ]);

                return [
                    'success' => false,
                    'message' => '授权失败',
                    'error' => [
                        'code' => $accessToken->getCode(),
                        'message' => $accessToken->getMsg(),
                        'sub_code' => $accessToken->getSubCode(),
                        'sub_msg' => $accessToken->getSubMsg(),
                    ]
                ];
            }
        } catch (\Exception $e) {
            $this->logger->error('处理授权回调异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => '处理授权回调失败: ' . $e->getMessage()
            ];
        }
    }

    // 获取已授权的商家列表
    // GET /doudian/tool-app/shops
    #[GetMapping('/shops')]
    public function getAuthorizedShops(): array
    {
        try {
            $shops = $this->toolAppManager->getAuthorizedShops();
            
            $this->logger->info('获取商家列表', [
                'shop_count' => count($shops),
            ]);
            
            return [
                'success' => true,
                'data' => [
                    'shops' => $shops,
                    'count' => count($shops),
                    'summary' => [
                        'total' => count($shops),
                        'active' => count(array_filter($shops, fn($shop) => !$shop['is_expired'])),
                        'expired' => count(array_filter($shops, fn($shop) => $shop['is_expired'])),
                    ]
                ]
            ];
        } catch (\Exception $e) {
            $this->logger->error('获取商家列表失败', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '获取商家列表失败: ' . $e->getMessage()
            ];
        }
    }

    // 代理商家调用 API - 获取商品列表
    // GET /doudian/tool-app/shop/{shopId}/products?page=0&size=20&status=1
    #[GetMapping('/shop/{shopId}/products')]
    public function getShopProducts(string $shopId, RequestInterface $request): array
    {
        try {
            $accessToken = $this->toolAppManager->getShopAccessToken($shopId);
            
            if (!$accessToken) {
                return [
                    'success' => false,
                    'message' => '商家未授权或令牌已过期',
                    'need_reauth' => true,
                ];
            }

            $client = $this->clientFactory->create();
            
            $productRequest = new ProductListRequest();
            $param = new ProductListParam();
            $param->page = (int) $request->input('page', 0);
            $param->size = min((int) $request->input('size', 20), 100);
            $param->status = $request->input('status') ? (int) $request->input('status') : null;
            $productRequest->setParam($param);

            $response = $client->request($productRequest, $accessToken);

            return [
                'success' => true,
                'data' => $response,
                'shop_info' => [
                    'shop_id' => $shopId,
                    'shop_name' => $this->toolAppManager->getShopInfo($shopId)['shop_name'] ?? '',
                ],
                'request_info' => [
                    'page' => $param->page,
                    'size' => $param->size,
                    'status' => $param->status,
                ]
            ];

        } catch (\Exception $e) {
            $this->logger->error('获取商品列表失败', [
                'shop_id' => $shopId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => '获取商品列表失败: ' . $e->getMessage()
            ];
        }
    }

    // 刷新商家访问令牌
    // POST /doudian/tool-app/shop/{shopId}/refresh-token
    #[PostMapping('/shop/{shopId}/refresh-token')]
    public function refreshShopToken(string $shopId, RequestInterface $request): array
    {
        $clientName = $request->input('client_name', 'default');

        try {
            $newAccessToken = $this->toolAppManager->refreshShopAccessToken($shopId, $clientName);
            
            if ($newAccessToken) {
                return [
                    'success' => true,
                    'message' => '令牌刷新成功',
                    'data' => [
                        'shop_id' => $newAccessToken->getShopId(),
                        'expires_in' => $newAccessToken->getExpireIn(),
                        'refreshed_at' => date('Y-m-d H:i:s'),
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'message' => '令牌刷新失败，可能需要重新授权',
                    'need_reauth' => true,
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '刷新令牌失败: ' . $e->getMessage()
            ];
        }
    }

    // 批量获取商家商品数据（协程并发）
    // GET /doudian/tool-app/batch/products?page=0&size=10
    #[GetMapping('/batch/products')]
    public function batchGetProducts(RequestInterface $request): array
    {
        try {
            $shops = $this->toolAppManager->getAuthorizedShops();
            $parallel = new \Hyperf\Coroutine\Parallel();
            
            $page = (int) $request->input('page', 0);
            $size = min((int) $request->input('size', 10), 50);

            foreach ($shops as $shopId => $shopInfo) {
                if (!$shopInfo['is_expired']) {
                    $parallel->add(function () use ($shopId, $page, $size) {
                        return $this->getShopProductsData($shopId, $page, $size);
                    });
                }
            }
            
            $results = $parallel->wait();
            
            return [
                'success' => true,
                'data' => [
                    'results' => $results,
                    'summary' => [
                        'total_shops' => count($shops),
                        'processed_shops' => count($results),
                        'success_count' => count(array_filter($results, fn($r) => $r['success'] ?? false)),
                    ],
                    'processed_at' => date('Y-m-d H:i:s'),
                ]
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '批量获取商品数据失败: ' . $e->getMessage()
            ];
        }
    }

    // 获取单个商家的商品数据（内部方法）
    private function getShopProductsData(string $shopId, int $page = 0, int $size = 10): array
    {
        try {
            $accessToken = $this->toolAppManager->getShopAccessToken($shopId);
            if (!$accessToken) {
                return [
                    'shop_id' => $shopId,
                    'success' => false,
                    'error' => '无法获取访问令牌'
                ];
            }
            
            $client = $this->clientFactory->create();
            $request = new ProductListRequest();
            $param = new ProductListParam();
            $param->page = $page;
            $param->size = $size;
            $request->setParam($param);
            
            $response = $client->request($request, $accessToken);
            
            return [
                'shop_id' => $shopId,
                'success' => true,
                'product_count' => property_exists($response, 'data') ? count($response->data) : 0,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'shop_id' => $shopId,
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // 系统监控 - 令牌状态概览
    // GET /doudian/tool-app/monitor/tokens
    #[GetMapping('/monitor/tokens')]
    public function monitorTokens(): array
    {
        try {
            $shops = $this->toolAppManager->getAuthorizedShops();
            $now = time();
            
            $stats = [
                'total' => count($shops),
                'active' => 0,
                'expired' => 0,
                'expiring_soon' => 0,
            ];
            
            $shopDetails = [];
            
            foreach ($shops as $shopId => $shopInfo) {
                $expiresIn = $shopInfo['expires_at'] - $now;
                
                if ($shopInfo['is_expired']) {
                    $stats['expired']++;
                } elseif ($expiresIn <= 86400) {
                    $stats['expiring_soon']++;
                } else {
                    $stats['active']++;
                }
                
                $shopDetails[] = [
                    'shop_id' => $shopId,
                    'shop_name' => $shopInfo['shop_name'],
                    'expires_at' => date('Y-m-d H:i:s', $shopInfo['expires_at']),
                    'expires_in_hours' => max(0, round($expiresIn / 3600, 1)),
                    'status' => $shopInfo['is_expired'] ? 'expired' : 
                               ($expiresIn <= 86400 ? 'warning' : 'ok'),
                ];
            }
            
            usort($shopDetails, fn($a, $b) => $a['expires_in_hours'] <=> $b['expires_in_hours']);
            
            return [
                'success' => true,
                'data' => [
                    'summary' => $stats,
                    'shop_details' => $shopDetails,
                    'checked_at' => date('Y-m-d H:i:s'),
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '监控数据获取失败: ' . $e->getMessage()
            ];
        }
    }
}
*/



// ===== 9. 定时任务示例 =====
/*
namespace App\Crontab;

use Doudian\Core\ToolAppManager;
use Hyperf\Crontab\Annotation\Crontab;
use Hyperf\Di\Annotation\Inject;

class TokenRefreshCrontab
{
    #[Inject]
    private ToolAppManager $toolAppManager;

    #[Crontab(rule: "0 * * * *", memo: "每小时检查并刷新过期的访问令牌")]
    public function refreshExpiredTokens(): void
    {
        $shops = $this->toolAppManager->getAuthorizedShops();
        
        foreach ($shops as $shopId => $shopInfo) {
            if ($shopInfo['is_expired']) {
                $this->toolAppManager->refreshShopAccessToken($shopId);
            }
        }
    }
}
*/

// ===== 10. 自定义 Token 存储配置 =====
/*
如果您不想使用数据库存储 token，可以使用以下方式：

方案一：使用回调函数，接入您的存储系统

// 在 config/autoload/dependencies.php 中配置：
return [
    \Doudian\Core\Contract\TokenStorageInterface::class => function () {
        return new \Doudian\Storage\CallbackTokenStorage(
            // 存储回调
            function (string $shopId, array $tokenData): bool {
                // 调用您的存储系统
                Redis::setex("doudian:token:$shopId", $tokenData['expires_in'], json_encode($tokenData));
                return true;
            },
            // 获取回调  
            function (string $shopId): ?array {
                $data = Redis::get("doudian:token:$shopId");
                return $data ? json_decode($data, true) : null;
            },
            // 删除回调
            function (string $shopId): bool {
                return Redis::del("doudian:token:$shopId") > 0;
            }
        );
    },
];

方案二：使用内存存储（适用于简单场景）

return [
    \Doudian\Core\Contract\TokenStorageInterface::class => \Doudian\Storage\MemoryTokenStorage::class,
];

详细示例请查看 examples/CustomTokenStorageExample.php
*/

// ===== 使用示例调用 =====
// generateAuthUrl();
// handleAuthCallback('your_auth_code', 'your_state');
// listAuthorizedShops();
// callApiForShop('your_shop_id');
// manageTokens();
// batchOperations(); 