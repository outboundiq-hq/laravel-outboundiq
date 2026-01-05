<?php

namespace OutboundIQ\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use OutboundIQ\Client;
use OutboundIQ\Configuration;
use OutboundIQ\Transports\QueueTransport;
use OutboundIQ\Laravel\Console\OutboundIQTestCommand;
use OutboundIQ\Laravel\Http\OutboundIQGuzzleClient;
use OutboundIQ\Laravel\Jobs\SendOutboundMetricsJob;
use OutboundIQ\Laravel\Services\OutboundIQService;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Events\ConnectionFailed;
use OutboundIQ\Laravel\Listeners\TrackHttpRequest;

class OutboundIQServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../../config/outboundiq.php', 'outboundiq');

        // Configure the queue transport dispatcher before creating the client
        $this->configureQueueTransport();

        $this->app->singleton(Client::class, function ($app) {
            return new Client(
                apiKey: config('outboundiq.api_key'),
                options: [
                    'enabled' => config('outboundiq.enabled', true),
                    'batch_size' => config('outboundiq.batch_size'),
                    'buffer_size' => config('outboundiq.buffer_size'),
                    'flush_interval' => config('outboundiq.flush_interval'),
                    'timeout' => config('outboundiq.timeout'),
                    'retry_attempts' => config('outboundiq.retry_attempts'),
                    'transport' => config('outboundiq.transport'),
                    'temp_dir' => config('outboundiq.temp_dir')
                ]
            );
        });

        // Bind the OutboundIQService for the Facade
        $this->app->singleton('outboundiq', function ($app) {
            return new OutboundIQService($app->make(Client::class));
        });

        // Also bind by class name for type-hinted injection
        $this->app->singleton(OutboundIQService::class, function ($app) {
            return $app->make('outboundiq');
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

            // Register Artisan commands
            $this->commands([
                OutboundIQTestCommand::class,
            ]);
        }

        // Register event listeners for Laravel HTTP Client
        $this->app['events']->listen(RequestSending::class, [TrackHttpRequest::class, 'handleSending']);
        $this->app['events']->listen(ResponseReceived::class, [TrackHttpRequest::class, 'handleResponse']);
        $this->app['events']->listen(ConnectionFailed::class, [TrackHttpRequest::class, 'handleFailure']);
    }

    /**
     * Configure the queue transport dispatcher for Laravel.
     * 
     * This sets up the QueueTransport to dispatch jobs via Laravel's queue system.
     */
    protected function configureQueueTransport(): void
    {
        QueueTransport::setDispatcher(function (array $metrics, Configuration $config) {
            SendOutboundMetricsJob::dispatch(
                metrics: $metrics,
                endpoint: $config->getEndpoint(),
                apiKey: $config->getApiKey(),
                version: $config->getVersion(),
                timeout: $config->getTimeout()
            )->onQueue(config('outboundiq.queue', 'default'));
        });
    }
}