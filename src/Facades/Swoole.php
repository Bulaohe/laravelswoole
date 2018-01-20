<?php

namespace Bulaohe\LaravelSwoole\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Mews\Captcha
 */
class Swoole extends Facade {

    /**
     * @return string
     */
    protected static function getFacadeAccessor() { return 'swoole'; }

}
