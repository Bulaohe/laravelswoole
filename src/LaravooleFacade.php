<?php

namespace Bulaohe\LaravelSwoole;

use Illuminate\Support\Facades\Facade;

class LaravooleFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'laswoole.server';
    }
}
