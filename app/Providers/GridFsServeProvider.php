<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MongoGrid\MongoGrid;

class GridFsServeProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */

    protected $defer = true;

    public function register()
    {
            $config = config('database.connections.mongodb');
            $confs['username'] = $config['username'];
            $confs['password'] = $config['password'];
            $confs['authSource'] = $config['database'];
            $this->app->singleton(MongoGrid::class, function ($app)use ($confs,$config) {
                return new MongoGrid($config['host'].':'.$config['port'],$confs);
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
        return [MongoGrid::class];
    }
}
