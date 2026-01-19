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

        $this->configureQueueTransport();

        $this->app->singleton(Client::class, fn() => new Client(
            apiKey: config('outboundiq.key'),
            options: [
                'enabled' => config('outboundiq.enabled', true),
                'transport' => config('outboundiq.transport', 'async'),
                'buffer_size' => config('outboundiq.max_items', 100),
                'url' => config('outboundiq.url', 'https://agent.outboundiq.dev/api/metric'),
            ]
        ));

        $this->app->singleton('outboundiq', fn($app) => new OutboundIQService($app->make(Client::class)));
        $this->app->singleton(OutboundIQService::class, fn($app) => $app->make('outboundiq'));
        $this->app->bind(OutboundIQGuzzleClient::class, fn($app) => new OutboundIQGuzzleClient($app->make(Client::class)));
        $this->app->singleton(TrackHttpRequest::class, fn($app) => new TrackHttpRequest($app->make(Client::class)));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../../config/outboundiq.php' => config_path('outboundiq.php'),
            ], 'config');

            $this->commands([OutboundIQTestCommand::class]);
        }

        // Auto-track Laravel HTTP client calls
        $this->app['events']->listen(RequestSending::class, [TrackHttpRequest::class, 'handleSending']);
        $this->app['events']->listen(ResponseReceived::class, [TrackHttpRequest::class, 'handleResponse']);
        $this->app['events']->listen(ConnectionFailed::class, [TrackHttpRequest::class, 'handleFailure']);
    }

    protected function configureQueueTransport(): void
    {
        QueueTransport::setDispatcher(function (array $metrics, Configuration $config) {
            $job = SendOutboundMetricsJob::dispatch(
                metrics: $metrics,
                endpoint: $config->getEndpoint(),
                apiKey: $config->getApiKey(),
                version: $config->getVersion(),
                timeout: $config->getTimeout()
            );

            // Only specify queue if explicitly configured, otherwise use app's default
            $queue = config('outboundiq.queue');
            if ($queue) {
                $job->onQueue($queue);
            }
        });
    }
}
