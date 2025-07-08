<?php

declare(strict_types=1);

namespace Doudian;

use Doudian\Core\ClientFactory;
use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\Contract\HttpClientInterface;
use Doudian\Core\Http\CoroutineHttpClient;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                HttpClientInterface::class => CoroutineHttpClient::class,
                ClientFactoryInterface::class => ClientFactory::class,
            ],
            'commands' => [
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'doudian-config',
                    'description' => '抖店 SDK 配置文件',
                    'source' => __DIR__ . '/../publish/doudian.php',
                    'destination' => BASE_PATH . '/config/autoload/doudian.php',
                ],
            ],
        ];
    }
} 