<?php

namespace OutboundIQ\Laravel\Services;

use OutboundIQ\Client;
use OutboundIQ\Interceptors\CurlInterceptor;
use OutboundIQ\Interceptors\GuzzleMiddleware;
use OutboundIQ\Interceptors\StreamWrapper;
use Illuminate\Support\Facades\Auth;

class OutboundIQService
{
    protected Client $client;
    
    /**
     * Manual user context override (for jobs/queues)
     */
    protected ?array $manualUserContext = null;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }
    
    /**
     * Get user context automatically from Laravel's auth system.
     * Zero effort - just works in web requests, gracefully handles jobs/console.
     */
    public function getUserContext(): array
    {
        // Check for manual override first (for jobs)
        if ($this->manualUserContext !== null) {
            $context = $this->manualUserContext;
            // Reset after use to prevent leaking between requests
            $this->manualUserContext = null;
            return $context;
        }
        
        return [
            'user_id' => Auth::id(),
            'user_type' => Auth::user() ? get_class(Auth::user()) : null,
            'context' => $this->detectContext(),
        ];
    }
    
    /**
     * Detect the execution context (authenticated, job, console, anonymous)
     */
    protected function detectContext(): string
    {
        if (!app()->runningInConsole()) {
            return Auth::check() ? 'authenticated' : 'anonymous';
        }
        
        // Check if we're in a queue worker processing a job
        try {
            if (app()->bound('queue.worker')) {
                return 'job';
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        return 'console';
    }
    
    /**
     * Set user context manually (useful for jobs/queues).
     * This is OPTIONAL - only use if you want to track a specific user in a job.
     * 
     * Usage:
     *   OutboundIQ::withUser($userId)->recommend('service');
     *   OutboundIQ::withUser($userId, User::class)->recommend('service');
     */
    public function withUser(int|string|null $userId, ?string $userType = null): self
    {
        $this->manualUserContext = [
            'user_id' => $userId,
            'user_type' => $userType,
            'context' => 'job',
        ];
        
        return $this;
    }
    
    /**
     * Set full user context manually.
     * 
     * Usage:
     *   OutboundIQ::setUserContext(['user_id' => 42, 'user_type' => User::class]);
     */
    public function setUserContext(array $context): self
    {
        $this->manualUserContext = array_merge([
            'user_id' => null,
            'user_type' => null,
            'context' => 'job',
        ], $context);
        
        return $this;
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
        string $request_type = 'manual',
        ?array $userContext = null
    ): void {
        // Auto-capture user context if not provided
        $userContext = $userContext ?? $this->getUserContext();
        
        $this->client->trackApiCall(
            url: $url,
            method: $method,
            duration: $duration,
            statusCode: $statusCode,
            requestHeaders: $requestHeaders,
            requestBody: $requestBody,
            responseHeaders: $responseHeaders,
            responseBody: $responseBody,
            request_type: $request_type,
            userContext: $userContext
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
     *   // Basic usage - user context captured automatically
     *   $result = OutboundIQ::recommend('payment-processing');
     *   
     *   // With custom request ID for tracing
     *   $result = OutboundIQ::recommend('payment-processing', [
     *       'request_id' => 'my-trace-id-123'
     *   ]);
     *   
     *   // In a job - optionally pass user_id
     *   $result = OutboundIQ::recommend('payment-processing', [
     *       'user_id' => $this->userId
     *   ]);
     *   
     *   // Or use fluent API for jobs
     *   $result = OutboundIQ::withUser($this->userId)->recommend('payment-processing');
     *   
     *   if ($result && $result['decision']['action'] === 'proceed') {
     *       // Use $result['decision']['use'] as the provider
     *   }
     *
     * @param string $serviceName The service name (e.g., 'payment-processing')
     * @param array $options Optional settings:
     *                       - 'request_id': Your own trace ID for correlation (auto-generated if not provided)
     *                       - 'user_id': User ID to track (auto-captured from auth if not provided)
     * @return array|null The recommendation response from server, or null on failure
     */
    public function recommend(string $serviceName, array $options = []): ?array
    {
        // Merge user context into options
        $userContext = $this->getUserContext();
        
        // Allow manual user_id override in options
        if (isset($options['user_id'])) {
            $userContext['user_id'] = $options['user_id'];
            $userContext['context'] = 'manual';
            unset($options['user_id']);
        }
        
        $options['user_context'] = $userContext;
        
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
     * @param array $options Optional settings:
     *                       - 'user_id': User ID to track (auto-captured from auth if not provided)
     * @return array|null The status response from server, or null on failure
     */
    public function providerStatus(string $providerSlug, array $options = []): ?array
    {
        // Merge user context into options
        $userContext = $this->getUserContext();
        
        if (isset($options['user_id'])) {
            $userContext['user_id'] = $options['user_id'];
            $userContext['context'] = 'manual';
            unset($options['user_id']);
        }
        
        $options['user_context'] = $userContext;
        
        return $this->client->providerStatus($providerSlug, $options);
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
     * @param array $options Optional settings:
     *                       - 'user_id': User ID to track (auto-captured from auth if not provided)
     * @return array|null The status response from server, or null on failure
     */
    public function endpointStatus(string $endpointSlug, array $options = []): ?array
    {
        // Merge user context into options
        $userContext = $this->getUserContext();
        
        if (isset($options['user_id'])) {
            $userContext['user_id'] = $options['user_id'];
            $userContext['context'] = 'manual';
            unset($options['user_id']);
        }
        
        $options['user_context'] = $userContext;
        
        return $this->client->endpointStatus($endpointSlug, $options);
    }
} 