<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OutboundIQ API Key
    |--------------------------------------------------------------------------
    |
    | Your OutboundIQ API key. Get it from your project settings.
    |
    */
    'key' => env('OUTBOUNDIQ_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Enable/Disable Monitoring
    |--------------------------------------------------------------------------
    |
    | Set to false to disable OutboundIQ in certain environments.
    |
    */
    'enabled' => env('OUTBOUNDIQ_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Remote URL
    |--------------------------------------------------------------------------
    |
    | The URL of the OutboundIQ agent to send metrics to.
    |
    */
    'url' => env('OUTBOUNDIQ_URL', 'https://agent.outboundiq.dev/api/metric'),

    /*
    |--------------------------------------------------------------------------
    | Transport Method
    |--------------------------------------------------------------------------
    |
    | How metrics are sent to OutboundIQ:
    | - 'async': Background process (default, best for traditional servers)
    | - 'sync': Blocking request (use for Laravel Vapor / AWS Lambda)
    | - 'queue': Laravel queue (use for Vapor with SQS)
    |
    */
    'transport' => env('OUTBOUNDIQ_TRANSPORT', 'async'),

    /*
    |--------------------------------------------------------------------------
    | Max Items
    |--------------------------------------------------------------------------
    |
    | Maximum number of API calls to buffer before sending.
    |
    */
    'max_items' => env('OUTBOUNDIQ_MAX_ITEMS', 100),

    /*
    |--------------------------------------------------------------------------
    | Queue Name (only when transport = 'queue')
    |--------------------------------------------------------------------------
    |
    | Which queue to use for sending metrics.
    |
    */
    'queue' => env('OUTBOUNDIQ_QUEUE'),
]; 