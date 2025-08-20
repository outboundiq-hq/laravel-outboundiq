<?php

namespace OutboundIQ\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use OutboundIQ\Client;
use OutboundIQ\Laravel\Http\OutboundIQGuzzleClient;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Events\ConnectionFailed;
use OutboundIQ\Laravel\Listeners\TrackHttpRequest;

class OutboundIQServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/outboundiq.php', 'outboundiq');

        $this->app->singleton(Client::class, function ($app) {
            return new Client(
                apiKey: config('outboundiq.api_key'),
                options: [
                    'enabled' => config('outboundiq.enabled', true),
                    'batch_size' => config('outboundiq.batch_size', 50),
                    'buffer_size' => config('outboundiq.buffer_size', 100),
                    'flush_interval' => config('outboundiq.flush_interval', 60),
                    'timeout' => config('outboundiq.timeout', 5),
                    'retry_attempts' => config('outboundiq.retry_attempts', 3),
                    'transport' => config('outboundiq.transport', 'file'),
                    'temp_dir' => config('outboundiq.temp_dir')
                ]
            );
        });

        $this->app->bind(OutboundIQGuzzleClient::class, function ($app) {
            return new OutboundIQGuzzleClient(
                outboundClient: $app->make(Client::class)
            );
        });

        // Register the HTTP request tracking listener
        $this->app->singleton(TrackHttpRequest::class, function ($app) {
            return new TrackHttpRequest($app->make(Client::class));
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/outboundiq.php' => config_path('outboundiq.php'),
            ], 'config');
        }

        // Register event listeners for Laravel HTTP Client
        $this->app['events']->listen(RequestSending::class, [TrackHttpRequest::class, 'handleSending']);
        $this->app['events']->listen(ResponseReceived::class, [TrackHttpRequest::class, 'handleResponse']);
        $this->app['events']->listen(ConnectionFailed::class, [TrackHttpRequest::class, 'handleFailure']);
    }
}