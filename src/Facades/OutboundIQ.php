<?php

namespace OutboundIQ\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

class OutboundIQ extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'outboundiq';
    }
} 