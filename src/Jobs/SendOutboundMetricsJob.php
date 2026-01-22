<?php

namespace OutboundIQ\Laravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOutboundMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(
        protected array $metrics,
        protected string $endpoint,
        protected string $apiKey,
        protected string $version,
        protected int $timeout = 10
    ) {}

    public function handle(): void
    {
        $jsonData = json_encode($this->metrics);
        if ($jsonData === false) {
            return;
        }

        $handle = curl_init($this->endpoint);
        curl_setopt_array($handle, [
            CURLOPT_POST => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'User-Agent: OutboundIQ-Laravel/' . $this->version,
            ],
            CURLOPT_POSTFIELDS => base64_encode($jsonData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        curl_exec($handle);
        $errorNo = curl_errno($handle);
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($errorNo !== 0 || ($httpCode < 200 || $httpCode >= 300)) {
            $errorMessage = "Failed to send metrics - HTTP $httpCode" . ($errorNo !== 0 ? " (cURL error: $errorNo)" : '');
            Log::warning('OutboundIQ: Metrics send failed', [
                'http_code' => $httpCode,
                'curl_error' => $errorNo,
                'endpoint' => $this->endpoint,
            ]);
            return;
        }
    }
}
