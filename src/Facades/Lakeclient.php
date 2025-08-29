<?php

namespace GustavoCaiano\Lakeclient\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \GustavoCaiano\Lakeclient\Lakeclient
 */
class Lakeclient extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \GustavoCaiano\Lakeclient\Lakeclient::class;
    }
}
