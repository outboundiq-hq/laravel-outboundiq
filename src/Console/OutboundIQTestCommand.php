<?php

declare(strict_types=1);

namespace OutboundIQ\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Artisan command to test OutboundIQ integration
 * 
 * Run with: php artisan outboundiq:test
 */
class OutboundIQTestCommand extends Command
{
    protected $signature = 'outboundiq:test';
    protected $description = 'Test OutboundIQ integration by making real HTTP calls that get tracked';

    public function handle(): int
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  OutboundIQ Integration Test');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $apiKey = config('outboundiq.api_key');
        $enabled = config('outboundiq.enabled', true);

        if (!$apiKey) {
            $this->error('✗ OUTBOUNDIQ_API_KEY is not configured');
            $this->line('');
            $this->line('Please add to your .env file:');
            $this->line('  OUTBOUNDIQ_API_KEY=your_api_key_here');
            return 1;
        }

        if (!$enabled) {
            $this->warn('⚠ OutboundIQ is disabled in configuration');
            $this->line('Set OUTBOUNDIQ_ENABLED=true in your .env to enable');
            return 1;
        }

        $this->line('📡 Making test HTTP calls (these will be tracked by OutboundIQ)...');
        $this->newLine();
        $this->line('   API Key: ' . substr($apiKey, 0, 20) . '...');
        $this->newLine();

        try {
            // Test 1: GET request
            $this->line('   → Making GET request to httpbin.org/get...');
            $response1 = Http::get('https://httpbin.org/get', [
                'source' => 'outboundiq-test',
                'framework' => 'laravel',
            ]);
            $this->info('   ✓ GET request completed (Status: ' . $response1->status() . ')');

            // Test 2: POST request
            $this->line('   → Making POST request to httpbin.org/post...');
            $response2 = Http::post('https://httpbin.org/post', [
                'source' => 'outboundiq-test',
                'framework' => 'laravel',
            ]);
            $this->info('   ✓ POST request completed (Status: ' . $response2->status() . ')');

            // Test 3: Another GET to a different endpoint
            $this->line('   → Making GET request to jsonplaceholder.typicode.com...');
            $response3 = Http::get('https://jsonplaceholder.typicode.com/posts/1');
            $this->info('   ✓ GET request completed (Status: ' . $response3->status() . ')');

            $this->newLine();
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->info('✓ All test requests completed!');
            $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            $this->newLine();
            $this->line('🎉 These HTTP calls have been automatically tracked by OutboundIQ!');
            $this->newLine();
            $this->line('   Check your dashboard to see the data:');
            $this->line('   <fg=blue>https://outboundiq.dev/dashboard</>');
            $this->newLine();

            return 0;
        } catch (\Throwable $e) {
            $this->error('✗ Failed to make test requests: ' . $e->getMessage());
            $this->newLine();
            $this->line('Please check:');
            $this->line('  1. You have internet connectivity');
            $this->line('  2. The test endpoints are reachable');
            return 1;
        }
    }
}

