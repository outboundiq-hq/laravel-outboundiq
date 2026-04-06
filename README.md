# Laravel OutboundIQ

**Website & dashboard:** [outboundiq.dev](https://outboundiq.dev) · **Documentation:** [outboundiq.dev/docs](https://outboundiq.dev/docs)

OutboundIQ for Laravel is **API Intelligence — not just monitoring**.

It automatically tracks every outbound API call your app makes and gives you *programmatic answers* to questions like:

- “Is this provider actually healthy for my traffic?”
- “Which provider should I use right now?”
- “Is a single endpoint degrading even though the status page is green?”

## Why this exists

If your app depends on third-party APIs, you’ve lived this:

- “Stripe is down? We found out from Twitter…”
- “Which provider should we failover to?”
- “Is it our code, or is the API having issues?”
- “Everything looks green… but users are failing.”

OutboundIQ makes those dependencies visible and actionable.

## What you get

### Automatic outbound-call tracking (Laravel-native)

OutboundIQ auto-tracks Laravel HTTP Client calls and captures:

- URL, method, status code, duration
- request/response headers + bodies (see security note below)
- failures + error messages
- user context (from `Auth::user()` when available)

### API Intelligence methods (the differentiator)

OutboundIQ exposes three methods you can call from your app:

- `OutboundIQ::recommend($serviceName)` → pick the best provider *right now*
- `OutboundIQ::providerStatus($providerSlug)` → pre-flight provider health checks
- `OutboundIQ::endpointStatus($endpointSlug)` → endpoint-level metrics + schema stability

## Quickstart (under 5 minutes)

### 1) Install

```bash
composer require outboundiq/laravel-outboundiq
```

### 2) Add your API key

Get your API key from [outboundiq.dev](https://outboundiq.dev) — sign in, open your project, and copy the key from project settings. Then add:

```env
OUTBOUNDIQ_KEY=your_api_key_here
```

### 3) (Optional) Publish config

```bash
php artisan vendor:publish --tag="outboundiq-config"
# or
php artisan vendor:publish --provider="OutboundIQ\Laravel\Providers\OutboundIQServiceProvider" --tag="config"
```

### 4) Run the integration test

This verifies config and makes tracked requests:

```bash
php artisan outboundiq:test
```

### 5) Make any outbound request

Laravel HTTP Client calls are tracked automatically:

```php
use Illuminate\Support\Facades\Http;

$response = Http::post('https://api.example.com/checkout', [
    'order_id' => 123,
]);
```

Open your OutboundIQ dashboard and you’ll see the transaction.

## API Intelligence examples

These examples show the decision contract you’ll build around: `decision.action` = `proceed` | `caution` | `avoid` | `unavailable`

### `recommend()`: choose the best provider before users feel failure

```php
use OutboundIQ\Laravel\Facades\OutboundIQ;

$decision = OutboundIQ::recommend('Payment Processing');

if (!$decision) {
    // OutboundIQ unavailable → use your default logic
    return $this->chargeWith('stripe', $order);
}

$action   = $decision['decision']['action'] ?? 'unavailable';
$provider = $decision['decision']['use'] ?? 'stripe';

return match ($action) {
    'proceed' => $this->chargeWith($provider, $order),
    'caution' => $this->chargeWithRetry($provider, $order, attempts: 3),
    'avoid'   => $this->chargeWith($decision['alternatives'][0]['provider']['slug'] ?? 'stripe', $order),
    default   => $this->chargeWith('stripe', $order),
};
```

### `providerStatus()`: pre-flight checks for critical operations

```php
use OutboundIQ\Laravel\Facades\OutboundIQ;

$status = OutboundIQ::providerStatus('twilio');

if (!$status) {
    return $this->sendViaTwilio($phone, $message); // default behavior
}

if (($status['decision']['action'] ?? null) === 'avoid') {
    return $this->sendViaBackupProvider($phone, $message);
}

// proceed | caution → your policy (retry, reduced timeouts, etc.)
return $this->sendViaTwilio($phone, $message);
```

### `endpointStatus()`: detect “one green dashboard, one broken endpoint”

Endpoint slugs are auto-generated (provider + method + path). Find them in the OutboundIQ dashboard under **Endpoints**.

```php
use OutboundIQ\Laravel\Facades\OutboundIQ;
use Illuminate\Support\Facades\Http;

$status = OutboundIQ::endpointStatus('stripe-post-v1-charges');

$timeout = 5;
if ($status && ($status['metrics']['avg_latency_ms'] ?? 0) > 0) {
    // Simple heuristic: 2× avg latency, converted to seconds (min 1)
    $timeout = max(1, (int) ceil(($status['metrics']['avg_latency_ms'] * 2) / 1000));
}

return Http::timeout($timeout)->post('https://api.stripe.com/v1/charges', $data);
```

## Tracking options

### Automatic tracking (Laravel HTTP Client)

Any `Http::get` / `Http::post` / … call is tracked automatically.

### Guzzle wrapper (if you call Guzzle directly)

```php
use OutboundIQ\Laravel\Http\OutboundIQGuzzleClient;

$guzzle = app(OutboundIQGuzzleClient::class);

$response = $guzzle->get('https://api.example.com/data');
$response = $guzzle->post('https://api.example.com/send', [
    'json' => ['message' => 'Hello!'],
]);
```

### Manual tracking (custom clients / edge cases)

```php
use OutboundIQ\Laravel\Facades\OutboundIQ;

OutboundIQ::trackApiCall(
    url: 'https://api.example.com/endpoint',
    method: 'POST',
    duration: 150.5,
    statusCode: 200,
    requestHeaders: ['Content-Type' => 'application/json'],
    requestBody: '{"key":"value"}',
    responseHeaders: ['Content-Type' => 'application/json'],
    responseBody: '{"status":"success"}'
);
```

## Configuration

You can configure OutboundIQ via `config/outboundiq.php` (or `.env`):

```env
# Required
OUTBOUNDIQ_KEY=your_api_key_here

# Optional
OUTBOUNDIQ_ENABLED=true

# Where metrics are sent
OUTBOUNDIQ_URL=https://agent.outboundiq.dev/api/metric

# Transport: async | sync | queue
# - async: background process (default for traditional servers)
# - sync: blocking (useful for some serverless runtimes)
# - queue: use Laravel queue workers
OUTBOUNDIQ_TRANSPORT=async

# Buffer size before flush
OUTBOUNDIQ_MAX_ITEMS=100

# Only used when OUTBOUNDIQ_TRANSPORT=queue
# Leave empty to use your app’s default queue
OUTBOUNDIQ_QUEUE=
```

## Troubleshooting

### No data showing up

- Confirm `OUTBOUNDIQ_KEY` is set
- Confirm `OUTBOUNDIQ_ENABLED=true`
- Run:

```bash
php artisan outboundiq:test
```

- Check Laravel logs (`storage/logs`) for errors when flushing metrics

## Requirements

- PHP 8.1+
- Laravel / Illuminate 10.x, 11.x, 12.x

## Security & privacy note

OutboundIQ tracks outbound-call metadata and can capture headers/bodies for debugging. Be intentional about what you send to third parties and review your account/project settings for any redaction or data controls.

## Support

Email: [hello@outboundiq.dev](mailto:hello@outboundiq.dev)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
