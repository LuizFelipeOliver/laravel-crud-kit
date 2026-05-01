<?php

namespace Example\LaravelCrudKit\Console;

use Example\LaravelCrudKit\Generators\Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeCommand extends Command
{
    protected $signature = 'kraken:make
        {name : Entity name}
        {--table= : Database table used to inspect model fields and relationships}
        {--api : Generate files using the API blueprint}
        {--web : Generate files using the Web/Inertia blueprint}
        {--only= : Generate only one file type: model, controller, service or repository}
        {--repository= : Repository type: simple or relations}';

    protected $description = 'Generate architecture files';

    public function handle(Generator $generator): int
    {
        if ($this->option('api') && $this->option('web')) {
            $this->components->error('Use only one blueprint option: --api or --web.');

            return self::FAILURE;
        }

        $only = $this->option('only');

        if ($only !== null && ! in_array($only, ['model', 'controller', 'service', 'repository'], true)) {
            $this->components->error('Invalid --only value. Use: model, controller, service or repository.');

            return self::FAILURE;
        }

        $repository = $this->option('repository');

        if ($repository !== null && ! in_array($repository, ['simple', 'relations'], true)) {
            $this->components->error('Invalid --repository value. Use: simple or relations.');

            return self::FAILURE;
        }

        $name = Str::studly($this->argument('name'));
        $blueprint = $this->option('web') ? 'web' : ($this->option('api') ? 'api' : null);

        $generator->generate(
            name: $name,
            table: $this->option('table'),
            blueprint: $blueprint,
            only: $only,
            repository: $repository,
        );

        $this->components->info("Files for {$name} generated.");

        return self::SUCCESS;
    }
}
