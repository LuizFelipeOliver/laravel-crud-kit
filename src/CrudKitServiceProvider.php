<?php

namespace Example\LaravelCrudKit;

use Example\LaravelCrudKit\Console\InstallCrudKitCommand;
use Example\LaravelCrudKit\Console\MakeCrudCommand;
use Illuminate\Support\ServiceProvider;

class CrudKitServiceProvider extends ServiceProvider
{
    public function register():void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/crud-kit.php',
            'crud-kit'
        );
    }

    public function boot(): void
    {
        if(! $this->app->runningInConsole()){
            return;
        }

        $this->commands([
            InstallCrudKitCommand::class,
            MakeCrudCommand::class,
        ]);

        $this->publishes([
            __DIR__ . '/../config/crud-kit.php' => config_path('crud-kit.php'),
        ], 'crud-kit-config');

        $this->publishes([
            __DIR__ . '/../stubs' => base_path('stubs/vendor/crud-kit'),
        ], 'crud-kit-stubs');
    }
}
