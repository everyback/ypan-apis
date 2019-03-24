<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use zip\zip;

class ZipserveProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    protected $defer = true;

    public function register()
    {
        //
        $this->app->singleton(zip::class, function ($app) {
            return new zip();
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    public function provides()
    {
        return [zip::class];
    }

}
