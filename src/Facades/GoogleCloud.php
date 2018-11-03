<?php

namespace Awescode\GoogleCloud\Facades;

use Illuminate\Support\Facades\Facade;

class GoogleCloud extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'googlecloud';
    }
}
