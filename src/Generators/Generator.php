<?php

namespace Example\LaravelCrudKit\Generators;

use Example\LaravelCrudKit\Blueprints\ApiBlueprint;
use Example\LaravelCrudKit\Blueprints\Blueprint;
use Example\LaravelCrudKit\Blueprints\WebBlueprint;
use Example\LaravelCrudKit\Filesystem\FileWriter;
use InvalidArgumentException;

final class Generator
{
    /**
     * @var array<string, \Example\LaravelCrudKit\Blueprints\Blueprint>
     */
    private array $blueprints;

    public function __construct(
        private GeneratorContextFactory $contextFactory,
        private FileWriter $writer,
        ApiBlueprint $apiBlueprint,
        WebBlueprint $webBlueprint,
    ) {
        $this->blueprints = [
            $apiBlueprint->name() => $apiBlueprint,
            $webBlueprint->name() => $webBlueprint,
        ];
    }

    public function generate(
        string $name,
        ?string $table = null,
        ?string $blueprint = null,
        ?string $only = null,
        ?string $repository = null,
        array $metadata = [],
    ): void {
        $context = $this->contextFactory->make(
            name: $name,
            table: $table,
            blueprint: $blueprint,
            only: $only,
            repository: $repository,
            metadata: $metadata,
        );

        foreach ($this->blueprint($context->blueprint)->files($context) as $file) {
            $this->writer->write($file);
        }
    }

    private function blueprint(string $name): Blueprint
    {
        if (! isset($this->blueprints[$name])) {
            throw new InvalidArgumentException("Blueprint [{$name}] is not supported.");
        }

        return $this->blueprints[$name];
    }
}
