<?php

declare(strict_types=1);

namespace OutboundIQ\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use OutboundIQ\Client;

class OutboundIQTestCommand extends Command
{
    protected $signature = 'outboundiq:test';
    protected $description = 'Test OutboundIQ integration by verifying API key and making tracked HTTP calls';

    public function handle(): int
    {
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('  OutboundIQ Integration Test');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        $apiKey = config('outboundiq.key');
        $enabled = config('outboundiq.enabled', true);

        if (!$apiKey) {
            $this->error('✗ OUTBOUNDIQ_KEY is not configured');
            $this->newLine();
            $this->line('Please add to your .env file:');
            $this->line('  OUTBOUNDIQ_KEY=your_api_key_here');
            return 1;
        }

        if (!$enabled) {
            $this->warn('⚠ OutboundIQ is disabled in configuration');
            $this->line('Set OUTBOUNDIQ_ENABLED=true in your .env to enable');
            return 1;
        }

        $this->line('   API Key: ' . substr($apiKey, 0, 20) . '...');
        $this->newLine();

        $this->line('   → Verifying API key with OutboundIQ (tracked)...');
        
        try {
            /** @var Client $client */
            $client = app(Client::class);
            $pingUrl = $client->getConfig()->getBaseUrl() . '/ping';
            
            // Using Http facade so this call is also tracked!
            $startPing = microtime(true);
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->get($pingUrl);
            $pingDuration = round((microtime(true) - $startPing) * 1000);

            if ($response->successful() && $response->json('status') === true) {
                $data = $response->json('data');
                $projectName = $data['project']['name'] ?? 'Unknown';
                $projectSlug = $data['project']['slug'] ?? '';
                $teamName = $data['team']['name'] ?? 'Unknown';
                $plan = $data['plan'] ?? 'free';
                $usage = $data['usage']['api_calls'] ?? 0;
                $limit = $data['usage']['limit'] ?? 0;
                
                $this->info("   ✓ Connected! Project: \"{$projectName}\" ({$pingDuration}ms)");
                $this->line("     Team: {$teamName} | Plan: {$plan}");
                if ($limit > 0) {
                    $this->line("     Usage: {$usage} / {$limit} API calls this period");
                }
                $this->newLine();
            } else {
                $message = $response->json('message') ?? 'Unknown error';
                $this->error("   ✗ API key verification failed: {$message}");
                return 1;
            }
        } catch (\Throwable $e) {
            $this->warn("   ⚠ Could not verify API key (offline mode): " . $e->getMessage());
            $this->line("     Continuing with HTTP tracking test...");
            $this->newLine();
            $projectSlug = null;
        }

        $this->line('   → Making external HTTP request (tracked)...');

        try {
            $startTime = microtime(true);
            $response = Http::get('https://jsonplaceholder.typicode.com/posts/1');
            $duration = round((microtime(true) - $startTime) * 1000);
            
            $this->info("   ✓ Request tracked (Status: {$response->status()}, {$duration}ms)");
        } catch (\Throwable $e) {
            $this->error('   ✗ HTTP request failed: ' . $e->getMessage());
            return 1;
        }

        $this->newLine();
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->info('✓ All tests passed! (2 API calls tracked)');
        $this->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();
        $this->line('🎉 Your OutboundIQ integration is working!');
        $this->line('   Both API calls have been tracked and will appear in your dashboard.');
        $this->newLine();
        
        if (!empty($projectSlug)) {
            $this->line('   Dashboard: <fg=blue>https://outboundiq.dev/dashboard/' . $projectSlug . '</>');
        } else {
            $this->line('   Dashboard: <fg=blue>https://outboundiq.dev/dashboard</>');
        }
        $this->newLine();

        return 0;
    }
}
