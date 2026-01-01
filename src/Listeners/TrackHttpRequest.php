<?php

namespace OutboundIQ\Laravel\Listeners;

use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Support\Facades\Auth;
use OutboundIQ\Client;

class TrackHttpRequest
{
    private array $requestTimes = [];
    private array $requestUserContext = [];

    /**
     * Create the event listener.
     */
    public function __construct(private Client $client)
    {
    }

    /**
     * Handle request starting.
     */
    public function handleSending(RequestSending $event): void
    {
        $key = $this->getRequestKey($event->request);
        
        // Store the start time for this request
        $this->requestTimes[$key] = microtime(true);
        
        // Capture user context at request time (important for async scenarios)
        $this->requestUserContext[$key] = $this->captureUserContext();
    }

    /**
     * Handle successful responses.
     */
    public function handleResponse(ResponseReceived $event): void
    {
        $key = $this->getRequestKey($event->request);
        $startTime = $this->getRequestStartTime($event->request);
        $userContext = $this->requestUserContext[$key] ?? $this->captureUserContext();
        
        $this->client->trackApiCall(
            url: (string) $event->request->url(),
            method: $event->request->method(),
            duration: $this->calculateDuration($startTime),
            statusCode: $event->response->status(),
            requestHeaders: $event->request->headers(),
            requestBody: $event->request->body(),
            responseHeaders: $event->response->headers(),
            responseBody: $event->response->body(),
            request_type: 'http',
            userContext: $userContext
        );

        // Clean up
        $this->removeRequestStartTime($event->request);
        unset($this->requestUserContext[$key]);
    }

    /**
     * Handle failed connections.
     */
    public function handleFailure(ConnectionFailed $event): void
    {
        $key = $this->getRequestKey($event->request);
        $startTime = $this->getRequestStartTime($event->request);
        $userContext = $this->requestUserContext[$key] ?? $this->captureUserContext();
        
        $this->client->trackApiCall(
            url: (string) $event->request->url(),
            method: $event->request->method(),
            duration: $this->calculateDuration($startTime),
            statusCode: 0,
            requestHeaders: $event->request->headers(),
            requestBody: $event->request->body(),
            responseHeaders: [],
            responseBody: null,
            request_type: 'http',
            error_message: $event->exception->getMessage(),
            userContext: $userContext
        );

        // Clean up
        $this->removeRequestStartTime($event->request);
        unset($this->requestUserContext[$key]);
    }
    
    /**
     * Capture user context from Laravel's auth system.
     */
    private function captureUserContext(): array
    {
        $context = 'anonymous';
        
        if (!app()->runningInConsole()) {
            $context = Auth::check() ? 'authenticated' : 'anonymous';
        } else {
            try {
                if (app()->bound('queue.worker')) {
                    $context = 'job';
                } else {
                    $context = 'console';
                }
            } catch (\Exception $e) {
                $context = 'console';
            }
        }
        
        return [
            'user_id' => Auth::id(),
            'user_type' => Auth::user() ? get_class(Auth::user()) : null,
            'context' => $context,
        ];
    }

    /**
     * Get a unique key for the request.
     */
    private function getRequestKey($request): string
    {
        return spl_object_hash($request);
    }

    /**
     * Get the start time for a request.
     */
    private function getRequestStartTime($request): float
    {
        return $this->requestTimes[$this->getRequestKey($request)] ?? microtime(true);
    }

    /**
     * Remove the start time for a request.
     */
    private function removeRequestStartTime($request): void
    {
        unset($this->requestTimes[$this->getRequestKey($request)]);
    }

    /**
     * Calculate request duration in milliseconds.
     */
    private function calculateDuration(float $startTime): float
    {
        return (microtime(true) - $startTime) * 1000;
    }
} 