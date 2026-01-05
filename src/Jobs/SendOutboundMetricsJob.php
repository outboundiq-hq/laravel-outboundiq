<?php

namespace OutboundIQ\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to send OutboundIQ metrics via queue.
 * 
 * This job is dispatched by the QueueTransport and sends metrics
 * to the OutboundIQ server in the background.
 * 
 * Perfect for Laravel Vapor / AWS Lambda where background processes don't work.
 */
class SendOutboundMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 5;

    /**
     * Metrics data to send.
     */
    protected array $metrics;

    /**
     * API endpoint.
     */
    protected string $endpoint;

    /**
     * API key.
     */
    protected string $apiKey;

    /**
     * Package version.
     */
    protected string $version;

    /**
     * Timeout in seconds.
     */
    protected int $timeout;

    /**
     * Create a new job instance.
     */
    public function __construct(
        array $metrics,
        string $endpoint,
        string $apiKey,
        string $version,
        int $timeout = 10
    ) {
        $this->metrics = $metrics;
        $this->endpoint = $endpoint;
        $this->apiKey = $apiKey;
        $this->version = $version;
        $this->timeout = $timeout;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $jsonData = json_encode($this->metrics);
        
        if ($jsonData === false) {
            return;
        }

        $encodedData = base64_encode($jsonData);

        $handle = curl_init($this->endpoint);

        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($handle, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($handle, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: OutboundIQ-Laravel/' . $this->version,
        ]);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $encodedData);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($handle);
        $errorNo = curl_errno($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($errorNo !== 0 || ($httpCode !== 200 && $httpCode !== 201)) {
            // Let the job retry
            throw new \Exception("OutboundIQ: Failed to send metrics - HTTP $httpCode, curl error: $errorNo");
        }

        curl_close($handle);
    }
}

