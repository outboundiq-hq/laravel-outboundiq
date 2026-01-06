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
OUTBOUNDIQ_ENABLED=true
OUTBOUNDIQ_ENDPOINT=null
OUTBOUNDIQ_BATCH_SIZE=50
OUTBOUNDIQ_BUFFER_SIZE=100
OUTBOUNDIQ_FLUSH_INTERVAL=60
OUTBOUNDIQ_TIMEOUT=5
OUTBOUNDIQ_RETRY_ATTEMPTS=3
OUTBOUNDIQ_TRANSPORT=file
OUTBOUNDIQ_TEMP_DIR=null
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information. 