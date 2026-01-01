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

    /**
     * Get status and metrics for a provider
     *
     * Returns real-time actionable data for decision-making:
     * - Provider status (from status page)
     * - Aggregate metrics (success rate, latency)
     * - Active incidents
     * - Affected components
     *
     * Usage:
     *   $status = OutboundIQ::providerStatus('paystack');
     *   
     *   if ($status && $status['decision']['usable']) {
     *       // Safe to make API calls to this provider
     *   } elseif ($status['decision']['action'] === 'avoid') {
     *       // Use fallback provider
     *   }
     *
     * @param string $providerSlug The provider slug (e.g., 'paystack')
     * @return array|null The status response from server, or null on failure
     */
    public function providerStatus(string $providerSlug): ?array
    {
        return $this->client->providerStatus($providerSlug);
    }

    /**
     * Get status and metrics for a specific endpoint
     *
     * Returns real-time actionable data for decision-making:
     * - Endpoint-specific metrics (success rate, latency, schema stability)
     * - Provider status (from status page)
     * - Active incidents
     * - Latency trend
     *
     * Usage:
     *   // Get endpoint slug from dashboard or use the auto-generated one
     *   $status = OutboundIQ::endpointStatus('paystack-post-transaction-initialize');
     *   
     *   if ($status && $status['decision']['usable']) {
     *       // Safe to call this endpoint
     *       // Check $status['metrics']['avg_latency_ms'] for expected latency
     *   } elseif ($status['decision']['action'] === 'caution') {
     *       // Proceed with extra error handling
     *   }
     *
     * @param string $endpointSlug The endpoint slug (e.g., 'paystack-post-transaction-initialize')
     * @return array|null The status response from server, or null on failure
     */
    public function endpointStatus(string $endpointSlug): ?array
    {
        return $this->client->endpointStatus($endpointSlug);
    }
} 