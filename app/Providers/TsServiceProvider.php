<?php

namespace App\Providers;

use App\TsWeb\Bot;
use App\TsWeb\Connectors\ServerQueryConnector;
use App\TsWeb\TsManager;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class TsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerManager();
        $this->registerConnection();
        $this->registerBot();
    }

    /**
     * Register the queue manager.
     *
     * @return void
     */
    protected function registerManager()
    {
        $this->app->singleton('ts', function(Application $app) {
            return tap(new TsManager($app), function($manager) {
                $this->registerConnectors($manager);
            });
        });

        $this->app->alias('ts', \App\TsWeb\TsManager::class);
    }

    /**
     * Register the default queue connection binding.
     *
     * @return void
     */
    protected function registerConnection()
    {
        $this->app->singleton('ts.connection', function (Application $app) {
            return $app['ts']->connection();
        });
    }

    /**
     * Register the connectors on the queue manager.
     *
     * @param  \App\TsWeb\TsManager  $manager
     * @return void
     */
    public function registerConnectors(TsManager $manager)
    {
        foreach (['ServerQuery'] as $connector) {
            $this->{"register{$connector}Connector"}($manager);
        }
    }

    /**
     * Register the serverquery connector.
     *
     * @param  \App\TsWeb\TsManager  $manager
     * @return void
     */
    protected function registerServerQueryConnector($manager)
    {
        $manager->addConnector('serverquery', function () {
            return new ServerQueryConnector();
        });
    }

    /**
     * Register the queue worker.
     *
     * @return void
     */
    protected function registerBot()
    {
        $this->app->singleton('ts.bot', function () {
            return new Bot($this->app['ts'], $this->app['events']);
        });

        $this->app->alias('ts.bot', \App\TsWeb\Bot::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'ts', 'ts.bot', 'ts.connection'
        ];
    }
}
