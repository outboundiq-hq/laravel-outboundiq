# Laravel OutboundIQ

Laravel integration for OutboundIQ - Third-party API monitoring and analytics.

## Installation

You can install the package via composer:

```bash
composer require outboundiq/laravel-outboundiq
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="outboundiq-config"
```

Add your OutboundIQ API key to your `.env` file:

```bash
OUTBOUNDIQ_KEY=your-api-key
```

## Usage

OutboundIQ will automatically monitor all HTTP requests made using Laravel's HTTP Client, Guzzle, cURL, or file_get_contents().

You can also manually track API calls:

```php
use OutboundIQ\Laravel\Facades\OutboundIQ;

OutboundIQ::trackApiCall(
    url: 'https://api.example.com/endpoint',
    method: 'POST',
    duration: 150.5,
    statusCode: 200,
    requestHeaders: ['Content-Type' => 'application/json'],
    requestBody: '{"key": "value"}',
    responseHeaders: ['Content-Type' => 'application/json'],
    responseBody: '{"status": "success"}'
);
```

## Configuration Options

You can configure OutboundIQ through your `.env` file:

```bash
# Required - your API key from OutboundIQ dashboard
OUTBOUNDIQ_KEY=your-api-key

# Enable/disable monitoring (default: true)
OUTBOUNDIQ_ENABLED=true

# Transport method: 'async', 'sync', or 'queue' (default: async)
# - async: Background process, best for traditional servers
# - sync: Blocking request, use for Laravel Vapor / AWS Lambda
# - queue: Laravel queue, use for Vapor with SQS
OUTBOUNDIQ_TRANSPORT=async

# Max items to buffer before sending (default: 100)
OUTBOUNDIQ_MAX_ITEMS=100

# Queue name when using 'queue' transport (default: default)
OUTBOUNDIQ_QUEUE=default

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 