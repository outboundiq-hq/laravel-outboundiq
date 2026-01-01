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

    /**
     * Get recommendation for a service
     *
     * Returns the best provider/endpoint to use based on:
     * - Your actual API usage data (success rate, latency, stability)
     * - Provider status page health
     * - Recent incidents
     *
     * Usage:
     *   // Basic usage
     *   $result = OutboundIQ::recommend('payment-processing');
     *   
     *   // With custom request ID for tracing
     *   $result = OutboundIQ::recommend('payment-processing', [
     *       'request_id' => 'my-trace-id-123'
     *   ]);
     *   
     *   if ($result && $result['decision']['action'] === 'proceed') {
     *       // Use $result['decision']['use'] as the provider
     *   }
     *
     * @param string $serviceName The service name (e.g., 'payment-processing')
     * @param array $options Optional settings:
     *                       - 'request_id': Your own trace ID for correlation (auto-generated if not provided)
     * @return array|null The recommendation response from server, or null on failure
     */
    public function recommend(string $serviceName, array $options = []): ?array
    {
        return $this->client->recommend($serviceName, $options);
    }
} 