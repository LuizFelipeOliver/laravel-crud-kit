<?php

namespace Example\LaravelCrudKit\Generators;

use Example\LaravelCrudKit\Filesystem\FileWriter;
use Example\LaravelCrudKit\Metadata\ModelMetadataResolver;
use InvalidArgumentException;
use Illuminate\Support\Str;

final class Generator
{
    /**
     * @var array<string, array{stub: string, path: string, namespace: string}>
     */
    private const CONTROLLERS = [
        'api' => [
            'stub' => 'api/controller.stub',
            'path' => 'api_controller',
            'namespace' => 'api_controller',
        ],
        'web' => [
            'stub' => 'web/controller.stub',
            'path' => 'web_controller',
            'namespace' => 'web_controller',
        ],
    ];

    public function __construct(
        private ModelMetadataResolver $metadataResolver,
        private FileWriter $writer,
    ) {}

    /**
     * @param array<string, mixed> $metadata
     * @return array{created: array<int, string>, skipped: array<int, string>}
     */
    public function generate(
        string $name,
        ?string $table = null,
        ?string $blueprint = null,
        ?string $only = null,
        ?string $repository = null,
        bool $withTests = false,
        array $metadata = [],
    ): array {
        $context = $this->makeContext(
            name: $name,
            table: $table,
            blueprint: $blueprint,
            only: $only,
            repository: $repository,
            withTests: $withTests,
            metadata: $metadata,
        );

        $created = [];
        $skipped = [];

        foreach ($this->files($context) as $file) {
            if ($this->writer->write($file)) {
                $created[] = $file->path;

                continue;
            }

            $skipped[] = $file->path;
        }

        return [
            'created' => $created,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function makeContext(
        string $name,
        ?string $table,
        ?string $blueprint,
        ?string $only,
        ?string $repository,
        bool $withTests,
        array $metadata,
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
            withTests: $withTests,
            paths: array_merge($this->defaultPaths(), config('generator.paths', [])),
            namespaces: array_merge($this->defaultNamespaces(), config('generator.namespaces', [])),
            metadata: array_merge(
                $this->defaultMetadata($resolvedTable),
                $this->shouldResolveMetadata($only, $resolvedRepository)
                    ? $this->metadataResolver->resolve($model, $resolvedTable)
                    : [],
                $metadata,
            ),
        );
    }

    /**
     * @return array<int, FileDefinition>
     */
    private function files(GeneratorContext $context): array
    {
        $generateTestsArtifacts = $context->withTests && $context->only === null;
        return array_values(array_filter([
            $context->shouldGenerate('model') ? $this->model($context) : null,
            $context->shouldGenerate('repository') ? $this->repository($context) : null,
            $context->shouldGenerate('service') ? $this->service($context) : null,
            $context->shouldGenerate('controller') ? $this->controller($context) : null,
            $context->shouldGenerate('controller') ? $this->route($context) : null,
            $context->withTests ? $this->factory($context) : null,
            $context->withTests ? $this->featureTest($context) : null,
            $generateTestsArtifacts ? $this->factory($context) : null,
            $generateTestsArtifacts ? $this->featureTest($context) : null,
        ]));
    }

    private function model(GeneratorContext $context): FileDefinition
    {
        return new FileDefinition(
            stub: 'shared/model.stub',
            path: $context->path('models', "{$context->model}.php"),
            replacements: [
                '{{ namespace }}' => $context->namespace('models'),
                '{{ name }}' => $context->model,
                '{{ imports }}' => $this->modelImports($context),
                '{{ attributes }}' => $context->metadata['attributes'] ?? '',
                '{{ uses }}' => $this->modelUses($context),
                '{{ casts }}' => $context->metadata['casts'] ?? '',
                '{{ relationships }}' => $context->metadata['relationships'] ?? '',
            ],
        );
    }

    private function route(GeneratorContext $context): FileDefinition
    {
        $stub = $context->blueprint === 'web' ? 'routes/web-resource.stub' : 'routes/api-resource.stub';
        $path = $context->blueprint === 'web' ? 'web_routes' : 'api_routes';
        $namespace = $context->blueprint === 'web' ? 'web_controller' : 'api_controller';

        return new FileDefinition(
            stub: $stub,
            path: $context->paths[$path],
            replacements: [
                '{{ controller_namespace }}' => $context->namespace($namespace),
                '{{ name }}' => $context->model,
                '{{ route }}' => $context->plural,
            ],
            mode: 'append',
        );
    }

    private function factory(GeneratorContext $context): FileDefinition
    {
        return new FileDefinition(
            stub: 'database/factory.stub',
            path: $context->path('factories', "{$context->model}Factory.php"),
            replacements: [
                '{{ namespace }}' => $context->namespace('factories'),
                '{{ model_namespace }}' => $context->namespace('models'),
                '{{ name }}' => $context->model,
                '{{ model }}' => $context->model,
            ],
        );
    }

    private function featureTest(GeneratorContext $context): FileDefinition
    {
        return new FileDefinition(
            stub: $context->blueprint === 'web' ? 'tests/web-feature.stub' : 'tests/api-feature.stub',
            path: $context->path($context->blueprint === 'web' ? 'web_tests' : 'api_tests', "{$context->model}ControllerTest.php"),
            replacements: [
                '{{ route }}' => $context->plural,
                '{{ name }}' => $context->model,
            ],
        );
    }

    private function repository(GeneratorContext $context): FileDefinition
    {
        return new FileDefinition(
            stub: $context->repository === 'relations' ? 'shared/repository-relations.stub' : 'shared/repository.stub',
            path: $context->path('repositories', "{$context->model}Repository.php"),
            replacements: [
                '{{ namespace }}' => $context->namespace('repositories'),
                '{{ model_namespace }}' => $context->namespace('models'),
                '{{ name }}' => $context->model,
                '{{ model }}' => $context->model,
                '{{ variable }}' => $context->variable,
                '{{ relations }}' => $context->metadata['repository_relations'] ?? '[]',
            ],
        );
    }

    private function service(GeneratorContext $context): FileDefinition
    {
        return new FileDefinition(
            stub: 'shared/service.stub',
            path: $context->path('services', "{$context->model}Service.php"),
            replacements: [
                '{{ namespace }}' => $context->namespace('services'),
                '{{ model_namespace }}' => $context->namespace('models'),
                '{{ repository_namespace }}' => $context->namespace('repositories'),
                '{{ name }}' => $context->model,
                '{{ model }}' => $context->model,
                '{{ variable }}' => $context->variable,
            ],
        );
    }

    private function controller(GeneratorContext $context): FileDefinition
    {
        $controller = self::CONTROLLERS[$context->blueprint] ?? null;

        if ($controller === null) {
            throw new InvalidArgumentException("Blueprint [{$context->blueprint}] is not supported.");
        }

        return new FileDefinition(
            stub: $controller['stub'],
            path: $context->path($controller['path'], "{$context->model}Controller.php"),
            replacements: [
                '{{ namespace }}' => $context->namespace($controller['namespace']),
                '{{ service_namespace }}' => $context->namespace('services'),
                '{{ name }}' => $context->model,
                '{{ model }}' => $context->model,
                '{{ variable }}' => $context->variable,
                '{{ route }}' => $context->plural,
            ],
        );
    }

    /**
     * @return array<string, string>
     */
    private function defaultPaths(): array
    {
        return [
            'api_controller' => app_path('Http/Controllers/Api'),
            'web_controller' => app_path('Http/Controllers'),
            'models' => app_path('Models'),
            'services' => app_path('Services'),
            'repositories' => app_path('Repositories'),
            'factories' => database_path('factories'),
            'api_tests' => base_path('tests/Feature/Api'),
            'web_tests' => base_path('tests/Feature/Web'),
            'api_routes' => base_path('routes/api.php'),
            'web_routes' => base_path('routes/web.php'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function defaultNamespaces(): array
    {
        return [
            'api_controller' => 'App\\Http\\Controllers\\Api',
            'web_controller' => 'App\\Http\\Controllers',
            'models' => 'App\\Models',
            'services' => 'App\\Services',
            'repositories' => 'App\\Repositories',
            'factories' => 'Database\\Factories',
        ];
    }

    private function modelImports(GeneratorContext $context): string
    {
        $imports = $context->metadata['imports'] ?? '';

        if (! $context->withTests) {
            return $imports;
        }

        return $imports . "\nuse Illuminate\\Database\\Eloquent\\Factories\\HasFactory;";
    }

    private function modelUses(GeneratorContext $context): string
    {
        $uses = array_filter([
            $context->withTests ? '    use HasFactory;' : '',
            $context->metadata['uses'] ?? '',
        ]);

        return implode("\n", $uses);
    }

    private function shouldResolveMetadata(?string $only, string $repository): bool
    {
        if ($only === null || $only === 'model') {
            return true;
        }

        return $only === 'repository' && $repository === 'relations';
    }

    /**
     * @return array<string, string>
     */
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
