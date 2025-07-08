<?php

declare(strict_types=1);

namespace Doudian\Core\Contract;

use Doudian\Core\Http\HttpRequest;
use Doudian\Core\Http\HttpResponse;

interface HttpClientInterface
{
    /**
     * 发送 GET 请求
     */
    public function get(HttpRequest $request): HttpResponse;

    /**
     * 发送 POST 请求
     */
    public function post(HttpRequest $request): HttpResponse;

    /**
     * 发送 PUT 请求
     */
    public function put(HttpRequest $request): HttpResponse;
} 