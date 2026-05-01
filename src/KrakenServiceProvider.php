<?php

namespace Example\LaravelCrudKit;

use Example\LaravelCrudKit\Console\InstallCommand;
use Example\LaravelCrudKit\Console\MakeCommand;
use Illuminate\Support\ServiceProvider;

class KrakenServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/generator.php',
            'generator'
        );
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            InstallCommand::class,
            MakeCommand::class,
        ]);

        $this->publishes([
            __DIR__ . '/../config/generator.php' => config_path('generator.php'),
        ], 'kraken-config');

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/vendor/kraken'),
        ], 'kraken-stubs');
    }
}
