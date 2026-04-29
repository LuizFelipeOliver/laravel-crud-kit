<?php

namespace Example\LaravelCrudKit;

use Illuminate\Support\ServiceProvider;
use Example\LaravelCrudKit\Console\MakeCrudCommand;

class CrudKitServiceProvider extends ServiceProvider
{
    public funcion register():void
    {
        $this->mergerConfigFrom(
            __DIR__ . '/.../config/crud-kit.php',
            'crud-kit'
        );
    }

    public function boot(): void
    {
        if(! $this->app->runningInConsole()){
            return;
        }

        $this->commands([
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
