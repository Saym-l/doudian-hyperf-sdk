<?php

declare(strict_types=1);

namespace Doudian\Core;

use Doudian\Core\Contract\ClientFactoryInterface;
use Doudian\Core\Contract\HttpClientInterface;
use Psr\Container\ContainerInterface;

class ClientFactory implements ClientFactoryInterface
{
    protected ContainerInterface $container;
    protected array $clients = [];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function create(): DoudianClient
    {
        return $this->get('default');
    }

    public function get(string $name): DoudianClient
    {
        if (!isset($this->clients[$name])) {
            $config = new Config($this->container, $name);
            $httpClient = new \Doudian\Core\Http\CoroutineHttpClient($this->container, $config);
            $this->clients[$name] = new DoudianClient($httpClient, $config);
        }

        return $this->clients[$name];
    }
} 