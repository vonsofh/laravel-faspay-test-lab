<?php

namespace Vonso\FaspayTestLab;

use Illuminate\Support\ServiceProvider;
use Vonso\FaspayTestLab\Commands\InstallCommand;

class FaspayTestLabServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/faspay-test-lab.php',
            'faspay-test-lab',
        );
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'faspay-test-lab');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);

            $this->publishes([
                __DIR__.'/../config/faspay-test-lab.php' => config_path('faspay-test-lab.php'),
            ], 'faspay-test-lab-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/faspay-test-lab'),
            ], 'faspay-test-lab-views');

            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'faspay-test-lab-migrations');

            $this->publishes([
                __DIR__.'/../templates/faspay' => storage_path('app/templates/faspay'),
            ], 'faspay-test-lab-templates');
        }
    }
}
