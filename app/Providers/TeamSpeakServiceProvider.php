<?php

namespace App\Providers;

use Teamspeak3;
use Illuminate\Support\ServiceProvider;

class TeamSpeakServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('teamspeak', function ($app) {
            return new Teamspeak3();
        });
    }
}
