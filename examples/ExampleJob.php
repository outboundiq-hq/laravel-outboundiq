<?php

namespace OutboundIQ\Laravel\Examples;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ExampleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        // This API call will be automatically tracked by OutboundIQ
        // even though it's running in an SQS queue
        $response = Http::get('https://api.example.com/data');

        // Process the response
        if ($response->successful()) {
            // Do something with the data
        }
    }
} 