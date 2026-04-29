<?php

namespace Acme\CrudKit\Console;

use Acme\CrudKit\Generators\CrudGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCrudCommand extends Command
{
    protected $signature = 'crud:make {name : Entity name}';

    protected $description = 'Generate layered CRUD files';

    public function handle(CrudGenerator $generator): int
    {
        $name = Str::studly($this->argument('name'));

        $generator->generate($name);

        $this->components->info("CRUD for {$name} generated.");

        return self::SUCCESS;
    }
}
