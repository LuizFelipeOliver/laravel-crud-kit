<?php

namespace Example\LaravelCrudKit\Metadata;

final readonly class MigrationRelationMetadata
{
    /**
     * @param array<int, string> $belongsTo
     * @param array<int, string> $pivot
     */
    public function __construct(
        public array $belongsTo = [],
        public array $pivot = [],
    ) {}

    public function hasRelations(): bool
    {
        if ($this->belongsTo !== []) {
            return true;
        }

        return $this->pivot !== [];
    }
}
