<?php

namespace OutboundIQ\Laravel\Listeners;

use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Events\ConnectionFailed;
use OutboundIQ\Client;

class TrackHttpRequest
{
    private array $requestTimes = [];

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
        // Store the start time for this request
        $this->requestTimes[$this->getRequestKey($event->request)] = microtime(true);
    }

    /**
     * Handle successful responses.
     */
    public function handleResponse(ResponseReceived $event): void
    {
        $startTime = $this->getRequestStartTime($event->request);
        
        $this->client->trackApiCall(
            url: (string) $event->request->url(),
            method: $event->request->method(),
            duration: $this->calculateDuration($startTime),
            statusCode: $event->response->status(),
            requestHeaders: $event->request->headers(),
            requestBody: $event->request->body(),
            responseHeaders: $event->response->headers(),
            responseBody: $event->response->body(),
            request_type: 'http'
        );

        // Clean up the start time
        $this->removeRequestStartTime($event->request);
    }

    /**
     * Handle failed connections.
     */
    public function handleFailure(ConnectionFailed $event): void
    {
        $startTime = $this->getRequestStartTime($event->request);
        
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
            error_message: $event->exception->getMessage()
        );

        // Clean up the start time
        $this->removeRequestStartTime($event->request);
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