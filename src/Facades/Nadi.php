<?php

namespace Nadi\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Nadi\Laravel\Nadi
 */
class Nadi extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Nadi\Laravel\Nadi::class;
    }
}
