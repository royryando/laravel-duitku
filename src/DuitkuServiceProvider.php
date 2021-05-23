<?php

namespace Royryando\Duitku;

use \Illuminate\Support\ServiceProvider;

class DuitkuServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/duitku.php' => config_path('duitku.php'),
        ]);
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/duitku.php', 'duitku');
        $this->app->bind('duitku', function() {
            return new DuitkuProcessor();
        });
    }
}
