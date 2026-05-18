<?php

namespace Example\LaravelCrudKit\Generators;

final readonly class FileDefinition
{
    public function __construct(
        public string $stub,
        public string $path,
        public array $replacements = [],
        public string $mode = 'write',
    ) {}
}
