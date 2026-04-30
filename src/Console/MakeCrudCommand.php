<?php

namespace Example\LaravelCrudKit\Console;

use Example\LaravelCrudKit\Generators\CrudGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    protected $signature = 'crud:make
        {name : Entity name}
        {--table= : Database table used to inspect model fields and relationships}';

    protected $description = 'Generate layered CRUD files';

    public function handle(CrudGenerator $generator): int
    {
        $name = Str::studly($this->argument('name'));

        $generator->generate($name, $this->option('table'));

        $this->components->info("CRUD for {$name} generated.");

        return self::SUCCESS;
    }
}
