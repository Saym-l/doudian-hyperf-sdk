<?php

declare(strict_types=1);

/**
 * 抖店 SDK Hyperf 使用示例
 * 
 * 此文件展示了如何在 Hyperf 框架中使用抖店 SDK
 */

use Doudian\Core\AccessTokenBuilder;
use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Api\Token\CreateTokenRequest;
use Doudian\Api\Token\Param\CreateTokenParam;
use Doudian\Api\Product\ProductListRequest;
use Doudian\Api\Product\Param\ProductListParam;
use Hyperf\Context\ApplicationContext;

// ===== 1. 基础配置 =====
// 在 config/autoload/doudian.php 中配置你的应用信息：
/*
return [
    'default' => [
        'app_key' => 'your_app_key',
        'app_secret' => 'your_app_secret',
        'open_request_url' => 'https://openapi-fxg.jinritemai.com',
        'http_connect_timeout' => 3,
        'http_read_timeout' => 10,
    ],
    'shops' => [
        'shop1' => [
            'app_key' => 'shop1_app_key',
            'app_secret' => 'shop1_app_secret',
        ],
    ],
];
*/

// ===== 2. 获取访问令牌示例 =====
function createAccessTokenExample(): void
{
    try {
        // 方式 1: 使用授权码获取访问令牌
        $accessToken = AccessTokenBuilder::build('your_authorization_code', AccessTokenBuilder::ACCESS_TOKEN_CODE);
        
        // 方式 2: 使用 shop_id 获取访问令牌（自用型应用）
        // $accessToken = AccessTokenBuilder::build('your_shop_id', AccessTokenBuilder::ACCESS_TOKEN_SHOP_ID);
        
        if ($accessToken->isSuccess()) {
            echo "访问令牌获取成功: " . $accessToken->getAccessToken() . "\n";
            echo "有效期: " . $accessToken->getExpireIn() . " 秒\n";
            echo "刷新令牌: " . $accessToken->getRefreshToken() . "\n";
        } else {
            echo "获取访问令牌失败: " . $accessToken->getMsg() . "\n";
        }
    } catch (Exception $e) {
        echo "异常: " . $e->getMessage() . "\n";
    }
}

// ===== 3. 刷新访问令牌示例 =====
function refreshAccessTokenExample(): void
{
    try {
        $refreshToken = 'your_refresh_token';
        $accessToken = AccessTokenBuilder::refresh($refreshToken);
        
        if ($accessToken->isSuccess()) {
            echo "令牌刷新成功: " . $accessToken->getAccessToken() . "\n";
        } else {
            echo "令牌刷新失败: " . $accessToken->getMsg() . "\n";
        }
    } catch (Exception $e) {
        echo "异常: " . $e->getMessage() . "\n";
    }
}

// ===== 4. 使用依赖注入的方式（推荐） =====
function dependencyInjectionExample(): void
{
    $container = ApplicationContext::getContainer();
    $clientFactory = $container->get(ClientFactoryInterface::class);
    
    // 获取默认客户端
    $client = $clientFactory->create();
    
    // 或者获取指定名称的客户端
    // $client = $clientFactory->get('shop1');
    
    // 创建访问令牌
    $accessToken = AccessTokenBuilder::parse('your_access_token_string');
    
    // 调用产品列表 API
    $request = new ProductListRequest();
    $param = new ProductListParam();
    $param->page = 0;
    $param->size = 10;
    $param->status = 1; // 上架状态
    $request->setParam($param);
    
    try {
        $response = $client->request($request, $accessToken);
        echo "API 调用成功: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
    } catch (Exception $e) {
        echo "API 调用失败: " . $e->getMessage() . "\n";
    }
}

// ===== 5. 在 Hyperf 控制器中使用示例 =====
/*
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
        // 获取客户端
        $client = $this->doudianClientFactory->create();
        
        // 构建访问令牌（实际应用中应该从数据库或缓存中获取）
        $accessToken = AccessTokenBuilder::parse('stored_access_token');
        
        // 构建请求
        $request = new ProductListRequest();
        $param = new ProductListParam();
        $param->page = 0;
        $param->size = 20;
        $request->setParam($param);
        
        // 发起请求
        $response = $client->request($request, $accessToken);
        
        return ['data' => $response];
    }
}
*/

// ===== 6. 在 Hyperf 服务类中使用示例 =====
/*
namespace App\Service;

use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\AccessToken;
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
}
*/ 