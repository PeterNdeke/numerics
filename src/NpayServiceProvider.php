<?php

namespace Numerics\Npay;

use Illuminate\Support\ServiceProvider;

class NpayServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    protected $defer = false;
    /**
    * Publishes all the config file this package needs to function
    */
    public function boot()
    {
        //
        $config = realpath(__DIR__.'/../resources/config/npay.php');
        $this->publishes([
            $config => config_path('npay.php')
        ]);
        include __DIR__.'/routes/route.php';
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
         $this->app->bind('npay', function () {
            return new Npay;
        });
    }
     public function provides()
    {
        return ['npay'];
    }
}
