<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\HandlerStack;
use OutboundIQ\Laravel\Http\CurlWrapper;

if (!function_exists('track_guzzle_client')) {
    /**
     * Create a new Guzzle client with OutboundIQ tracking or add tracking to an existing client
     *
     * @param GuzzleClient|array|null $clientOrConfig
     * @return GuzzleClient
     */
    function track_guzzle_client(GuzzleClient|array|null $clientOrConfig = null): GuzzleClient
    {
        $stack = app('outboundiq.handler');
        
        if ($clientOrConfig instanceof GuzzleClient) {
            // Get the existing handler stack
            $config = $clientOrConfig->getConfig();
            $handler = $config['handler'] ?? HandlerStack::create();
            
            // Add our middleware
            if ($handler instanceof HandlerStack) {
                foreach ($stack->getMiddleware() as $name => $middleware) {
                    $handler->push($middleware, $name);
                }
            }
            
            // Create new client with updated handler
            return new GuzzleClient(['handler' => $handler] + $config);
        }
        
        // Create new client with our handler
        $config = is_array($clientOrConfig) ? $clientOrConfig : [];
        $config['handler'] = $stack;
        
        return new GuzzleClient($config);
    }
}

if (!function_exists('outboundiq_curl_exec')) {
    /**
     * Execute a cURL request with OutboundIQ tracking
     *
     * @param resource|CurlHandle $ch
     * @return bool|string
     */
    function outboundiq_curl_exec($ch) {
        /** @var CurlWrapper $wrapper */
        $wrapper = app()->make(CurlWrapper::class);
        return $wrapper->exec($ch);
    }
}

if (!function_exists('outboundiq_curl_setopt')) {
    /**
     * Set a cURL option with OutboundIQ tracking
     *
     * @param resource|CurlHandle $ch
     * @param int $option
     * @param mixed $value
     * @return bool
     */
    function outboundiq_curl_setopt($ch, $option, $value) {
        /** @var CurlWrapper $wrapper */
        $wrapper = app()->make(CurlWrapper::class);
        return $wrapper->setopt($ch, $option, $value);
    }
}

if (!function_exists('outboundiq_curl_setopt_array')) {
    /**
     * Set multiple cURL options with OutboundIQ tracking
     *
     * @param resource|CurlHandle $ch
     * @param array $options
     * @return bool
     */
    function outboundiq_curl_setopt_array($ch, array $options) {
        /** @var CurlWrapper $wrapper */
        $wrapper = app()->make(CurlWrapper::class);
        return $wrapper->setopt_array($ch, $options);
    }
} 