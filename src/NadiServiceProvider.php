<?php

namespace Nadi\Laravel;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Nadi\Laravel\Console\Commands\InstallCommand;
use Nadi\Laravel\Console\Commands\TestCommand;
use Nadi\Laravel\Console\Commands\UpdateShipperCommand;
use Nadi\Laravel\Console\Commands\VerifyCommand;
use Nadi\Laravel\Middleware\OpenTelemetryMiddleware;

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
                UpdateShipperCommand::class,
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

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (! config('nadi.enabled')) {
            return;
        }

        // Register OpenTelemetry middleware if using OpenTelemetry driver
        if (config('nadi.driver') === 'opentelemetry') {
            $this->registerOpenTelemetryMiddleware();
        }
    }

    /**
     * Register OpenTelemetry middleware
     */
    private function registerOpenTelemetryMiddleware(): void
    {
        $router = $this->app->make(Router::class);

        // Register the middleware
        $router->aliasMiddleware('nadi.otel', OpenTelemetryMiddleware::class);

        // Optionally auto-register middleware for web routes if configured
        if (config('nadi.connections.opentelemetry.auto_instrument_web', false)) {
            $router->pushMiddlewareToGroup('web', OpenTelemetryMiddleware::class);
        }

        // Optionally auto-register middleware for API routes if configured
        if (config('nadi.connections.opentelemetry.auto_instrument_api', false)) {
            $router->pushMiddlewareToGroup('api', OpenTelemetryMiddleware::class);
        }
    }
}
