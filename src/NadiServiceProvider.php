<?php

namespace Nadi\Laravel;

use Nadi\Laravel\Console\Commands\InstallCommand;
use Nadi\Laravel\Console\Commands\TestCommand;
use Nadi\Laravel\Console\Commands\VerifyCommand;
use Illuminate\Support\ServiceProvider;

class NadiServiceProvider extends ServiceProvider
{
    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        $this->publishes([
            __DIR__.'/../config/nadi.php' => config_path('nadi.php'),
        ], 'nadi-config');

        $this->mergeConfigFrom(
            __DIR__.'/../config/nadi.php', 'nadi'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                TestCommand::class,
                VerifyCommand::class,
            ]);
        }

        if (! config('nadi.enabled')) {
            return;
        }

        app()->singleton('nadi', function () {
            return \Nadi\Laravel\Transporter::make();
        });

        foreach (config('nadi.observe') as $event => $listeners) {
            foreach ($listeners as $listener) {
                app()['events']->listen($event, $listener);
            }
        }

    }
}
