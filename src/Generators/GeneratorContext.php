<?php

namespace Example\LaravelCrudKit\Generators;

final readonly class GeneratorContext
{
    public function __construct(
        public string $name,
        public string $model,
        public string $variable,
        public string $plural,
        public string $table,
        public string $blueprint,
        public ?string $only,
        public string $repository,
        public bool $withTests,
        public array $paths,
        public array $namespaces,
        public array $metadata = [],
    ) {}

    public function path(string $key, string $filename): string
    {
        return rtrim($this->paths[$key], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;
    }

    public function namespace(string $key): string
    {
        return $this->namespaces[$key];
    }

    public function shouldGenerate(string $type): bool
    {
        return $this->only === null || $this->only === $type;
    }
}
