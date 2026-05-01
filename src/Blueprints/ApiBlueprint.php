<?php

namespace Example\LaravelCrudKit\Blueprints;

use Example\LaravelCrudKit\Generators\FileDefinition;
use Example\LaravelCrudKit\Generators\GeneratorContext;

final class ApiBlueprint implements Blueprint
{
    public function name(): string
    {
        return 'api';
    }

    public function files(GeneratorContext $context): array
    {
        return array_values(array_filter([
            $context->shouldGenerate('model') ? $this->model($context) : null,
            $context->shouldGenerate('repository') ? $this->repository($context) : null,
            $context->shouldGenerate('service') ? $this->service($context) : null,
            $context->shouldGenerate('controller') ? $this->controller($context) : null,
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
                '{{ imports }}' => $context->metadata['imports'] ?? '',
                '{{ attributes }}' => $context->metadata['attributes'] ?? '',
                '{{ uses }}' => $context->metadata['uses'] ?? '',
                '{{ casts }}' => $context->metadata['casts'] ?? '',
                '{{ relationships }}' => $context->metadata['relationships'] ?? '',
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
                '{{ repository_namespace }}' => $context->namespace('repositories'),
                '{{ name }}' => $context->model,
                '{{ model }}' => $context->model,
                '{{ variable }}' => $context->variable,
            ],
        );
    }

    private function controller(GeneratorContext $context): FileDefinition
    {
        return new FileDefinition(
            stub: 'api/controller.stub',
            path: $context->path('api_controller', "{$context->model}Controller.php"),
            replacements: [
                '{{ namespace }}' => $context->namespace('api_controller'),
                '{{ service_namespace }}' => $context->namespace('services'),
                '{{ name }}' => $context->model,
                '{{ model }}' => $context->model,
                '{{ variable }}' => $context->variable,
                '{{ route }}' => $context->plural,
            ],
        );
    }
}
