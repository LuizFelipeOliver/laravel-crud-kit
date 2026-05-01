<?php

namespace Example\LaravelCrudKit\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'kraken:install {--force : Overwrite existing published files}';

    protected $description = 'Publish Kraken generator configuration and stubs';

    public function handle(): int
    {
        $options = [
            '--tag' => ['kraken-config', 'kraken-stubs'],
        ];

        if ($this->option('force')) {
            $options['--force'] = true;
        }

        $this->call('vendor:publish', $options);

        $this->components->info('Kraken generator files published.');

        return self::SUCCESS;
    }
}
