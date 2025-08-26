<?php

namespace GustavoCaiano\Windclient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \GustavoCaiano\Windclient\Windclient
 */
class Windclient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \GustavoCaiano\Windclient\Windclient::class;
    }
}
