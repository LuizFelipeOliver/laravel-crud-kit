<?php

namespace Example\LaravelCrudKit\Blueprints;

use Example\LaravelCrudKit\Generators\GeneratorContext;

interface Blueprint
{
    public function name(): string;

    /**
     * @return array<int, \Example\LaravelCrudKit\Generators\FileDefinition>
     */
    public function files(GeneratorContext $context): array;
}
