<?php

namespace App\Providers;

use TeamSpeak3;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('teamspeak', function ($app) {
            $uri = "serverquery://serveradmin:AwxRnhwR@sksystems.de:10011/?server_port=9987";

            return Teamspeak3::factory($uri);
        });
    }
}
