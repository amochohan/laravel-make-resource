<?php

namespace DrawMyAttention\ResourceGenerator;

use Illuminate\Support\ServiceProvider;

class ResourceGeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.drawmyattention.makeresource', function ($app) {
            return $app['DrawMyAttention\ResourceGenerator\Commands\ResourceMakeCommand'];
        });
        $this->commands('command.drawmyattention.makeresource');

    }

}