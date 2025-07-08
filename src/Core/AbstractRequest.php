<?php

declare(strict_types=1);

namespace Doudian\Core;

use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\Contract\RequestInterface;
use Hyperf\Context\ApplicationContext;

abstract class AbstractRequest implements RequestInterface
{
    protected ?object $param = null;

    public function getParam(): ?object
    {
        return $this->param;
    }

    public function setParam(object $param): void
    {
        $this->param = $param;
    }

    /**
     * 子类必须实现此方法返回具体的 URL 路径
     */
    abstract public function getUrlPath(): string;

    /**
     * 执行请求
     */
    public function execute(?AccessToken $accessToken = null, string $clientName = 'default'): object
    {
        $container = ApplicationContext::getContainer();
        $clientFactory = $container->get(ClientFactoryInterface::class);
        $client = $clientFactory->get($clientName);
        
        return $client->request($this, $accessToken);
    }
} 