<?php

declare(strict_types=1);

namespace Doudian\Core;

use Hyperf\Contract\ConfigInterface;
use Psr\Container\ContainerInterface;

class Config
{
    protected array $config;

    public function __construct(ContainerInterface $container, string $name = 'default')
    {
        $configInterface = $container->get(ConfigInterface::class);
        $allConfig = $configInterface->get('doudian', []);
        
        if ($name === 'default') {
            $this->config = $allConfig['default'] ?? [];
        } else {
            $this->config = $allConfig['shops'][$name] ?? $allConfig['default'] ?? [];
        }
        
        $this->validateConfig();
    }

    public function getAppKey(): string
    {
        return $this->config['app_key'] ?? '';
    }

    public function getAppSecret(): string
    {
        return $this->config['app_secret'] ?? '';
    }

    public function getOpenRequestUrl(): string
    {
        return $this->config['open_request_url'] ?? 'https://openapi-fxg.jinritemai.com';
    }

    public function getHttpConnectTimeout(): int
    {
        return $this->config['http_connect_timeout'] ?? 3;
    }

    public function getHttpReadTimeout(): int
    {
        return $this->config['http_read_timeout'] ?? 10;
    }

    public function getPoolConfig(): array
    {
        return $this->config['pool'] ?? [
            'min_connections' => 1,
            'max_connections' => 10,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 60.0,
        ];
    }

    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    protected function validateConfig(): void
    {
        if (empty($this->config['app_key'])) {
            throw new \InvalidArgumentException('抖店 SDK app_key 不能为空');
        }

        if (empty($this->config['app_secret'])) {
            throw new \InvalidArgumentException('抖店 SDK app_secret 不能为空');
        }
    }
} 