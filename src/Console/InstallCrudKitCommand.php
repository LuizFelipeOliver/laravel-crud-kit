<?php

namespace Example\LaravelCrudKit\Console;

use Illuminate\Console\Command;

class InstallCrudKitCommand extends Command
{
    protected $signature = 'crud:install {--force : Overwrite existing published files}';

    protected $description = 'Publish Laravel CRUD Kit configuration and stubs';

    public function handle(): int
    {
        $options = [
            '--tag' => ['crud-kit-config', 'crud-kit-stubs'],
        ];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        $this->call('vendor:publish', $options);

        $this->components->info('Laravel CRUD Kit files published.');

        return self::SUCCESS;
    }
}
