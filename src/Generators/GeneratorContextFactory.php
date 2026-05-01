<?php

namespace Example\LaravelCrudKit\Generators;

use Example\LaravelCrudKit\Metadata\ModelMetadataResolver;
use Illuminate\Support\Str;

final class GeneratorContextFactory
{
    public function __construct(
        private ModelMetadataResolver $metadataResolver,
    ) {}

    public function make(
        string $name,
        ?string $table = null,
        ?string $blueprint = null,
        ?string $only = null,
        ?string $repository = null,
        array $metadata = [],
    ): GeneratorContext {
        $model = Str::studly($name);
        $resolvedTable = $table ?? Str::snake(Str::pluralStudly($model));
        $resolvedRepository = $repository ?? config('generator.repository.default', 'simple');

        return new GeneratorContext(
            name: $name,
            model: $model,
            variable: Str::camel($name),
            plural: Str::plural(Str::kebab($name)),
            table: $resolvedTable,
            blueprint: $blueprint ?? config('generator.default_blueprint', 'api'),
            only: $only,
            repository: $resolvedRepository,
            paths: array_merge([
                'api_controller' => app_path('Http/Controllers/Api'),
                'web_controller' => app_path('Http/Controllers'),
                'models' => app_path('Models'),
                'services' => app_path('Services'),
                'repositories' => app_path('Repositories'),
            ], config('generator.paths', [])),
            namespaces: array_merge([
                'api_controller' => 'App\\Http\\Controllers\\Api',
                'web_controller' => 'App\\Http\\Controllers',
                'models' => 'App\\Models',
                'services' => 'App\\Services',
                'repositories' => 'App\\Repositories',
            ], config('generator.namespaces', [])),
            metadata: array_merge(
                $this->defaultMetadata($resolvedTable),
                $this->shouldResolveMetadata($only, $resolvedRepository)
                    ? $this->metadataResolver->resolve($model, $resolvedTable)
                    : [],
                $metadata,
            ),
        );
    }

    private function shouldResolveMetadata(?string $only, string $repository): bool
    {
        if ($only === null || $only === 'model') {
            return true;
        }

        return $only === 'repository' && $repository === 'relations';
    }

    private function defaultMetadata(string $table): array
    {
        return [
            'imports' => implode("\n", [
                'use Illuminate\\Database\\Eloquent\\Attributes\\Table;',
                'use Illuminate\\Database\\Eloquent\\Model;',
            ]),
            'attributes' => "#[Table('{$table}')]",
            'uses' => '',
            'casts' => <<<PHP
    protected function casts(): array
    {
        return [];
    }
PHP,
            'relationships' => '',
            'repository_relations' => '[]',
        ];
    }
}
