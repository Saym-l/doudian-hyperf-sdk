<?php

declare(strict_types=1);

namespace Doudian\Core;

use Doudian\Core\Contract\HttpClientInterface;
use Doudian\Core\Contract\RequestInterface;
use Doudian\Core\Http\HttpRequest;
use Doudian\Utils\SignUtil;

class DoudianClient
{
    protected HttpClientInterface $httpClient;
    protected Config $config;

    public function __construct(HttpClientInterface $httpClient, Config $config)
    {
        $this->httpClient = $httpClient;
        $this->config = $config;
    }

    public function request(RequestInterface $request, ?AccessToken $accessToken = null): object
    {
        $urlPath = $request->getUrlPath();
        $method = $this->getMethod($urlPath);
        $paramJson = SignUtil::marshal($request->getParam());
        $appKey = $this->config->getAppKey();
        $appSecret = $this->config->getAppSecret();
        $timestamp = time();
        $sign = SignUtil::sign($appKey, $appSecret, $method, $timestamp, $paramJson);

        $openHost = $this->config->getOpenRequestUrl();
        $accessTokenStr = '';
        if ($accessToken !== null) {
            $accessTokenStr = $accessToken->getAccessToken();
        }

        $requestUrl = sprintf(
            '%s%s?app_key=%s&method=%s&v=2&sign=%s&timestamp=%s&access_token=%s&sign_method=hmac-sha256',
            $openHost,
            $urlPath,
            $appKey,
            $method,
            $sign,
            $timestamp,
            $accessTokenStr
        );

        $httpRequest = new HttpRequest(
            $requestUrl,
            $paramJson,
            $this->config->getHttpConnectTimeout(),
            $this->config->getHttpReadTimeout()
        );

        $httpResponse = $this->httpClient->post($httpRequest);

        return json_decode($httpResponse->body, false, 512, JSON_UNESCAPED_UNICODE);
    }

    protected function getMethod(string $urlPath): string
    {
        if (empty($urlPath)) {
            return $urlPath;
        }

        $methodPath = ltrim($urlPath, '/');
        return str_replace('/', '.', $methodPath);
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
} 