<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OutboundIQ API Key
    |--------------------------------------------------------------------------
    |
    | Your OutboundIQ API key. This is required for the package to work.
    |
    */
    'api_key' => env('OUTBOUNDIQ_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Monitoring
    |--------------------------------------------------------------------------
    |
    | Set this to false to disable monitoring in certain environments.
    |
    */
    'enabled' => env('OUTBOUNDIQ_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Batch Settings
    |--------------------------------------------------------------------------
    |
    | Configure how metrics are batched and sent to OutboundIQ.
    |
    */
    'batch_size' => env('OUTBOUNDIQ_BATCH_SIZE', 50),
    'buffer_size' => env('OUTBOUNDIQ_BUFFER_SIZE', 100),
    'flush_interval' => env('OUTBOUNDIQ_FLUSH_INTERVAL', 60),

    /*
    |--------------------------------------------------------------------------
    | Request Settings
    |--------------------------------------------------------------------------
    |
    | Configure timeout and retry settings for API calls.
    |
    */
    'timeout' => env('OUTBOUNDIQ_TIMEOUT', 5),
    'retry_attempts' => env('OUTBOUNDIQ_RETRY_ATTEMPTS', 3),

    /*
    |--------------------------------------------------------------------------
    | Transport Settings
    |--------------------------------------------------------------------------
    |
    | Configure how metrics are transported to OutboundIQ.
    |
    */
    'transport' => env('OUTBOUNDIQ_TRANSPORT', 'file'),
    'temp_dir' => env('OUTBOUNDIQ_TEMP_DIR', null),
]; 