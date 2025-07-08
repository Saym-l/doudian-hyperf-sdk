<?php

declare(strict_types=1);

namespace Doudian\Core\Http;

use Doudian\Core\Contract\HttpClientInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Hyperf\Guzzle\ClientFactory;
use Psr\Container\ContainerInterface;

class CoroutineHttpClient implements HttpClientInterface
{
    protected Client $client;

    public function __construct(ContainerInterface $container)
    {
        $clientFactory = $container->get(ClientFactory::class);
        $this->client = $clientFactory->create();
    }

    public function get(HttpRequest $request): HttpResponse
    {
        try {
            $response = $this->client->get($request->url, [
                RequestOptions::HEADERS => $this->buildHeaders($request->headers),
                RequestOptions::TIMEOUT => $request->readTimeout,
                RequestOptions::CONNECT_TIMEOUT => $request->connectTimeout,
            ]);

            return new HttpResponse(
                $response->getStatusCode(),
                $response->getBody()->getContents(),
                $this->parseHeaders($response->getHeaders())
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                return new HttpResponse(
                    $response->getStatusCode(),
                    $response->getBody()->getContents(),
                    $this->parseHeaders($response->getHeaders())
                );
            }
            throw $e;
        }
    }

    public function post(HttpRequest $request): HttpResponse
    {
        try {
            $options = [
                RequestOptions::HEADERS => $this->buildHeaders($request->headers),
                RequestOptions::TIMEOUT => $request->readTimeout,
                RequestOptions::CONNECT_TIMEOUT => $request->connectTimeout,
            ];

            if (!empty($request->body)) {
                $options[RequestOptions::BODY] = $request->body;
            }

            $response = $this->client->post($request->url, $options);

            return new HttpResponse(
                $response->getStatusCode(),
                $response->getBody()->getContents(),
                $this->parseHeaders($response->getHeaders())
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                return new HttpResponse(
                    $response->getStatusCode(),
                    $response->getBody()->getContents(),
                    $this->parseHeaders($response->getHeaders())
                );
            }
            throw $e;
        }
    }

    public function put(HttpRequest $request): HttpResponse
    {
        try {
            $options = [
                RequestOptions::HEADERS => $this->buildHeaders($request->headers),
                RequestOptions::TIMEOUT => $request->readTimeout,
                RequestOptions::CONNECT_TIMEOUT => $request->connectTimeout,
            ];

            if (!empty($request->body)) {
                $options[RequestOptions::BODY] = $request->body;
            }

            $response = $this->client->put($request->url, $options);

            return new HttpResponse(
                $response->getStatusCode(),
                $response->getBody()->getContents(),
                $this->parseHeaders($response->getHeaders())
            );
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if ($response) {
                return new HttpResponse(
                    $response->getStatusCode(),
                    $response->getBody()->getContents(),
                    $this->parseHeaders($response->getHeaders())
                );
            }
            throw $e;
        }
    }

    protected function buildHeaders(array $headers): array
    {
        $defaultHeaders = [
            'Content-Type' => 'application/json;charset=utf-8',
            'Accept' => 'application/json',
            'from' => 'sdk',
            'sdk-type' => 'php',
            'sdk-version' => 'doudian-hyperf-sdk-1.0.0',
            'x-open-no-old-err-code' => '1',
        ];

        return array_merge($defaultHeaders, $headers);
    }

    protected function parseHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $name => $values) {
            $result[$name] = is_array($values) ? implode(', ', $values) : $values;
        }
        return $result;
    }
} 