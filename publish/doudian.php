<?php

declare(strict_types=1);

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
        // 'shop1' => [
        //     'app_key' => 'your_app_key_1',
        //     'app_secret' => 'your_app_secret_1',
        //     'open_request_url' => 'https://openapi-fxg.jinritemai.com',
        //     'http_connect_timeout' => 3,
        //     'http_read_timeout' => 10,
        // ],
        // 'shop2' => [
        //     'app_key' => 'your_app_key_2',
        //     'app_secret' => 'your_app_secret_2',
        //     'open_request_url' => 'https://openapi-fxg.jinritemai.com',
        //     'http_connect_timeout' => 3,
        //     'http_read_timeout' => 10,
        // ],
    ],
]; 