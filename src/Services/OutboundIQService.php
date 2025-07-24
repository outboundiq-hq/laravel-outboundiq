<?php

namespace OutboundIQ\Laravel\Services;

use OutboundIQ\Client;
use OutboundIQ\Interceptors\CurlInterceptor;
use OutboundIQ\Interceptors\GuzzleMiddleware;
use OutboundIQ\Interceptors\StreamWrapper;

class OutboundIQService
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function registerInterceptors(): void
    {
        // Register Guzzle middleware (this also covers Laravel's HTTP Client)
        GuzzleMiddleware::register($this->client);

        // Register cURL interceptor
        CurlInterceptor::register($this->client);

        // Register file_get_contents interceptor
        StreamWrapper::register($this->client);
    }

    public function trackApiCall(
        string $url,
        string $method,
        float $duration,
        int $statusCode,
        array $requestHeaders = [],
        ?string $requestBody = null,
        array $responseHeaders = [],
        ?string $responseBody = null,
        string $request_type = 'manual'
    ): void {
        $this->client->trackApiCall(
            url: $url,
            method: $method,
            duration: $duration,
            statusCode: $statusCode,
            requestHeaders: $requestHeaders,
            requestBody: $requestBody,
            responseHeaders: $responseHeaders,
            responseBody: $responseBody,
            request_type: $request_type
        );
    }
} 