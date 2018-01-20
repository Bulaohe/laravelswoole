<?php

namespace Bulaohe\Laravelswoole;

use Illuminate\Support\ServiceProvider;

class SwooleServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     * @var bool
     */
    protected $defer = true;

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/swoole.php', 'swoole'
        );
        
        $this->commands([
            Commands\SwooleCommand::class,
        ]);
    }
    
    /**
     * publish swoole config file
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/swoole.php' => config_path('swoole.php')
        ], 'config');
    }
}