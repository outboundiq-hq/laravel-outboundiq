<?php

namespace OutboundIQ\Laravel\Traits;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;

trait HasGuzzleClient
{
    protected function createGuzzleClient(array $config = []): GuzzleClient
    {
        // Get the handler stack from the container
        if (!isset($config['handler'])) {
            $stack = app(HandlerStack::class);
            $config['handler'] = clone $stack; // Clone to avoid sharing state
        }
        
        return new GuzzleClient($config);
    }
} 