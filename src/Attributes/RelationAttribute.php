<?php

namespace Example\LaravelCrudKit\Attributes;

abstract readonly class RelationAttribute
{
    /**
     * @var array<int, string>
     */
    public array $relations;

    /**
     * @param array<int, string>|string $relations
     */
    public function __construct(array|string $relations)
    {
        $this->relations = $this->normalize($relations);
    }

    /**
     * @param array<int, string>|string $relations
     * @return array<int, string>
     */
    private function normalize(array|string $relations): array
    {
        if (is_string($relations)) {
            return [$relations];
        }

        $normalized = [];

        foreach ($relations as $relation) {
            if (! is_string($relation)) {
                continue;
            }

            $relation = trim($relation);

            if ($relation === '') {
                continue;
            }

            $normalized[$relation] = $relation;
        }

        return array_values($normalized);
    }
}
