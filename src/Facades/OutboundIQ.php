<?php

namespace OutboundIQ\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static void trackApiCall(string $url, string $method, float $duration, int $statusCode, array $requestHeaders = [], ?string $requestBody = null, array $responseHeaders = [], ?string $responseBody = null, string $request_type = 'manual')
 * @method static array|null recommend(string $serviceName, array $options = [])
 * @method static array|null providerStatus(string $providerSlug)
 * @method static array|null endpointStatus(string $endpointSlug)
 * 
 * @see \OutboundIQ\Laravel\Services\OutboundIQService
 */
class OutboundIQ extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'outboundiq';
    }
} 