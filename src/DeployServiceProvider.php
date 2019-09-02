<?php

namespace Woaap\Deploy;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

class DeployServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot(Router $router)
    {
        $this->publishes([
            __DIR__.'/Config/deploy.php' => config_path('deploy.php'),
        ], 'deploy_config');

        $this->loadRoutesFrom(__DIR__ . '/Routes/web.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                \Woaap\Deploy\Commands\SyncEnv::class,
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/Config/deploy.php', 'deploy'
        );
    }
}
