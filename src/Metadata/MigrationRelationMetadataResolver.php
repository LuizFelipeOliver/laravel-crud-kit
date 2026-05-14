<?php

namespace Example\LaravelCrudKit\Metadata;

use Example\LaravelCrudKit\Attributes\BelongsTo;
use Example\LaravelCrudKit\Attributes\BelongsToMany;
use Example\LaravelCrudKit\Attributes\Pivot;
use Example\LaravelCrudKit\Attributes\RelationAttribute;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;

final class MigrationRelationMetadataResolver
{
    /**
     * @return array<int, array{table: string, relations: array<int, string>}>
     */
    public function pivots(): array
    {
        $pivots = [];

        foreach ($this->createMigrationPaths() as $path) {
            $pivot = $this->pivotFromPath($path);

            if ($pivot === null) {
                continue;
            }

            $pivots[] = $pivot;
        }

        return $pivots;
    }

    public function resolve(string $table): MigrationRelationMetadata
    {
        $migration = $this->migration($table);

        if ($migration === null) {
            return new MigrationRelationMetadata();
        }

        $reflection = new ReflectionClass($migration);

        return new MigrationRelationMetadata(
            belongsTo: $this->relations($reflection, BelongsTo::class),
            belongsToMany: $this->relations($reflection, BelongsToMany::class),
            pivot: $this->relations($reflection, Pivot::class),
        );
    }

    private function migration(string $table): ?object
    {
        $path = $this->migrationPath($table);

        if ($path === null) {
            return null;
        }

        return $this->migrationFromPath($path);
    }

    /**
     * @return array{table: string, relations: array<int, string>}|null
     */
    private function pivotFromPath(string $path): ?array
    {
        $table = $this->tableFromCreateMigrationPath($path);

        if ($table === null) {
            return null;
        }

        $migration = $this->migrationFromPath($path);

        if ($migration === null) {
            return null;
        }

        $relations = $this->relations(new ReflectionClass($migration), Pivot::class);

        if (count($relations) !== 2) {
            return null;
        }

        if (! $this->matchesPivotConvention($table, $relations)) {
            return null;
        }

        return [
            'table' => $table,
            'relations' => $relations,
        ];
    }

    private function migrationPath(string $table): ?string
    {
        $paths = File::glob(base_path("database/migrations/*_create_{$table}_table.php"));

        if ($paths === false || $paths === []) {
            return null;
        }

        sort($paths);

        return $paths[0];
    }

    /**
     * @return array<int, string>
     */
    private function createMigrationPaths(): array
    {
        $paths = File::glob(base_path('database/migrations/*_create_*_table.php'));

        if ($paths === false || $paths === []) {
            return [];
        }

        sort($paths);

        return $paths;
    }

    private function migrationFromPath(string $path): ?object
    {
        $migration = require $path;

        if (! is_object($migration)) {
            return null;
        }

        return $migration;
    }

    private function tableFromCreateMigrationPath(string $path): ?string
    {
        $filename = basename($path);

        if (! preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_create_(.+)_table\.php$/', $filename, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param array<int, string> $relations
     */
    private function matchesPivotConvention(string $table, array $relations): bool
    {
        $expectedTable = array_map(
            fn(string $relation): string => Str::singular(Str::snake($relation)),
            $relations
        );

        sort($expectedTable);

        return $table === implode('_', $expectedTable);
    }

    /**
     * @param class-string<RelationAttribute> $attribute
     * @return array<int, string>
     */
    private function relations(ReflectionClass $reflection, string $attribute): array
    {
        $attributes = $reflection->getAttributes($attribute);

        if ($attributes === []) {
            return [];
        }

        $instance = $attributes[0]->newInstance();

        if (! $instance instanceof RelationAttribute) {
            return [];
        }

        return $instance->relations;
    }
}
