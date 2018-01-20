<?php
namespace Bulaohe\Laravelswoole\Illuminate;

class Application extends \Illuminate\Foundation\Application
{
    public function isProviderLoaded($name)
    {
        return isset($this->loadedProviders[$name]);
    }
}
