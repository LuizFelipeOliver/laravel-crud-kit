<?php

namespace Example\LaravelCrudKit\Console;

use Example\LaravelCrudKit\Generators\Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Throwable;

class MakeCommand extends Command
{
    private const ONLY_OPTIONS = ['model', 'controller', 'service', 'repository'];

    private const REPOSITORY_OPTIONS = ['simple', 'relations'];

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
        $startedAt = microtime(true);

        if ($this->hasConflictingBlueprintOptions()) {
            return self::FAILURE;
        }

        if (! $this->hasValidOnlyOption()) {
            return self::FAILURE;
        }

        if (! $this->hasValidRepositoryOption()) {
            return self::FAILURE;
        }

        $name = Str::studly($this->argument('name'));

        try {
            $result = $generator->generate(
                name: $name,
                table: $this->option('table'),
                blueprint: $this->blueprint(),
                only: $this->stringOption('only'),
                repository: $this->stringOption('repository'),
            );
        } catch (Throwable $exception) {
            $this->components->error('Kraken could not generate files: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info("Files for {$name} generated.");
        $this->displayPaths('Created files', $result['created']);
        $this->displayPaths('Skipped existing files', $result['skipped']);
        $this->components->info('Completed in ' . $this->elapsedTime($startedAt) . '.');

        return self::SUCCESS;
    }

    private function hasConflictingBlueprintOptions(): bool
    {
        if (! $this->option('api') || ! $this->option('web')) {
            return false;
        }

        $this->components->error('Use only one blueprint option: --api or --web.');

        return true;
    }

    private function hasValidOnlyOption(): bool
    {
        if ($this->isValidOption('only', self::ONLY_OPTIONS)) {
            return true;
        }

        $this->components->error('Invalid --only value. Use: model, controller, service or repository.');

        return false;
    }

    private function hasValidRepositoryOption(): bool
    {
        if ($this->isValidOption('repository', self::REPOSITORY_OPTIONS)) {
            return true;
        }

        $this->components->error('Invalid --repository value. Use: simple or relations.');

        return false;
    }

    /**
     * @param array<int, string> $allowed
     */
    private function isValidOption(string $option, array $allowed): bool
    {
        $value = $this->stringOption($option);

        if ($value === null) {
            return true;
        }

        return in_array($value, $allowed, true);
    }

    private function blueprint(): ?string
    {
        if ($this->option('web')) {
            return 'web';
        }

        if ($this->option('api')) {
            return 'api';
        }

        return null;
    }

    private function stringOption(string $option): ?string
    {
        $value = $this->option($option);

        return is_string($value) ? $value : null;
    }

    /**
     * @param array<int, string> $paths
     */
    private function displayPaths(string $title, array $paths): void
    {
        if ($paths === []) {
            return;
        }

        $this->line($title . ':');

        foreach ($paths as $path) {
            $this->line("  - {$path}");
        }
    }

    private function elapsedTime(float $startedAt): string
    {
        return number_format(microtime(true) - $startedAt, 2) . 's';
    }
}
