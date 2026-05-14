<?php

namespace Example\LaravelCrudKit\Metadata;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class RelationshipResolver
{
    private const BELONGS_TO_IMPORT = 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo';

    private const BELONGS_TO_MANY_IMPORT = 'Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany';

    public function __construct(
        private MigrationRelationMetadataResolver $migrationMetadataResolver,
    ) {}

    /**
     * @param array<int, array<string, mixed>> $columns
     * @return array{imports: array<int, string>, methods: string, names: array<int, string>}
     */
    public function resolve(string $model, string $table, array $columns): array
    {
        $metadata = $this->migrationMetadataResolver->resolve($table);
        $belongsToMany = $this->declaredBelongsToManyRelations($table);

        if ($metadata->belongsTo !== []) {
            return $this->format(
                model: $model,
                belongsTo: $this->declaredBelongsToRelations($metadata->belongsTo, $columns),
                belongsToMany: $belongsToMany,
            );
        }

        return $this->format(
            model: $model,
            belongsTo: $this->automaticBelongsToRelations($table, $columns),
            belongsToMany: $belongsToMany,
        );
    }

    /**
     * @param array<int, string> $declaredRelations
     * @param array<int, array<string, mixed>> $columns
     * @return array<int, array{column: string, method: string, related_model: string}>
     */
    private function declaredBelongsToRelations(array $declaredRelations, array $columns): array
    {
        $relations = [];
        $columnNames = $this->columnNames($columns);

        foreach ($declaredRelations as $relation) {
            $column = Str::snake($relation) . '_id';

            if (! in_array($column, $columnNames, true)) {
                continue;
            }

            $relations[] = [
                'column' => $column,
                'method' => Str::camel($relation),
                'related_model' => Str::studly($relation),
            ];
        }

        return $relations;
    }

    /**
     * @return array<int, array{method: string, related_model: string}>
     */
    private function declaredBelongsToManyRelations(string $table): array
    {
        $relations = [];
        $currentRelation = Str::singular($table);

        foreach ($this->migrationMetadataResolver->pivots() as $pivot) {
            $pivotRelations = $pivot['relations'];

            if (! in_array($currentRelation, $pivotRelations, true)) {
                continue;
            }

            if (! $this->hasPivotForeignKeys($pivot['table'], $pivotRelations)) {
                continue;
            }

            $relatedRelation = $this->relatedPivotRelation($currentRelation, $pivotRelations);

            if ($relatedRelation === null) {
                continue;
            }

            $relations[] = [
                'method' => Str::camel(Str::plural($relatedRelation)),
                'related_model' => Str::studly($relatedRelation),
            ];
        }

        return $relations;
    }

    /**
     * @param array<int, string> $relations
     */
    private function hasPivotForeignKeys(string $table, array $relations): bool
    {
        if (! Schema::hasTable($table)) {
            return false;
        }

        $columns = $this->columnNames(Schema::getColumns($table));

        foreach ($relations as $relation) {
            if (! in_array(Str::snake($relation) . '_id', $columns, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $relations
     */
    private function relatedPivotRelation(string $currentRelation, array $relations): ?string
    {
        foreach ($relations as $relation) {
            if ($relation === $currentRelation) {
                continue;
            }

            return $relation;
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $columns
     * @return array<int, array{column: string, method: string, related_model: string}>
     */
    private function automaticBelongsToRelations(string $table, array $columns): array
    {
        $relations = [];
        $belongsToColumns = [];

        foreach ($this->foreignKeys($table) as $foreignKey) {
            $relation = $this->belongsToFromForeignKey($foreignKey);

            if ($relation === null) {
                continue;
            }

            $belongsToColumns[] = $relation['column'];
            $relations[] = $relation;
        }

        foreach ($columns as $column) {
            $relation = $this->belongsToFromColumn($column, $belongsToColumns);

            if ($relation === null) {
                continue;
            }

            $relations[] = $relation;
        }

        return $relations;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function foreignKeys(string $table): array
    {
        if (! method_exists(Schema::getFacadeRoot(), 'getForeignKeys')) {
            return [];
        }

        return Schema::getForeignKeys($table);
    }

    /**
     * @param array<string, mixed> $foreignKey
     * @return array{column: string, method: string, related_model: string}|null
     */
    private function belongsToFromForeignKey(array $foreignKey): ?array
    {
        $column = $this->foreignKeyColumn($foreignKey);
        $foreignTable = $this->foreignKeyTable($foreignKey);

        if ($column === null || $foreignTable === null) {
            return null;
        }

        return [
            'column' => $column,
            'method' => $this->belongsToMethodName($column, $foreignTable),
            'related_model' => Str::studly(Str::singular($foreignTable)),
        ];
    }

    /**
     * @param array<string, mixed> $column
     * @param array<int, string> $ignoredColumns
     * @return array{column: string, method: string, related_model: string}|null
     */
    private function belongsToFromColumn(array $column, array $ignoredColumns): ?array
    {
        $columnName = $this->columnName($column);

        if (! Str::endsWith($columnName, '_id')) {
            return null;
        }

        if (in_array($columnName, $ignoredColumns, true)) {
            return null;
        }

        $baseName = Str::beforeLast($columnName, '_id');

        return [
            'column' => $columnName,
            'method' => Str::camel($baseName),
            'related_model' => Str::studly($baseName),
        ];
    }

    /**
     * @param array<int, array{column: string, method: string, related_model: string}> $belongsTo
     * @param array<int, array{method: string, related_model: string}> $belongsToMany
     * @return array{imports: array<int, string>, methods: string, names: array<int, string>}
     */
    private function format(string $model, array $belongsTo, array $belongsToMany): array
    {
        if ($belongsTo === [] && $belongsToMany === []) {
            return [
                'imports' => [],
                'methods' => '',
                'names' => [],
            ];
        }

        $belongsTo = $this->uniqueBelongsToRelations($belongsTo);
        $belongsToMany = $this->uniqueBelongsToManyRelations($belongsToMany);

        return [
            'imports' => $this->imports($belongsTo, $belongsToMany),
            'methods' => implode("\n\n", [
                ...array_map(
                    fn(array $relation): string => $this->formatBelongsToMethod($model, $relation),
                    $belongsTo
                ),
                ...array_map(
                    fn(array $relation): string => $this->formatBelongsToManyMethod($model, $relation),
                    $belongsToMany
                ),
            ]),
            'names' => [
                ...array_column($belongsTo, 'method'),
                ...array_column($belongsToMany, 'method'),
            ],
        ];
    }

    /**
     * @param array<int, array{column: string, method: string, related_model: string}> $belongsTo
     * @param array<int, array{method: string, related_model: string}> $belongsToMany
     * @return array<int, string>
     */
    private function imports(array $belongsTo, array $belongsToMany): array
    {
        $imports = [];

        if ($belongsTo !== []) {
            $imports[] = self::BELONGS_TO_IMPORT;
        }

        if ($belongsToMany !== []) {
            $imports[] = self::BELONGS_TO_MANY_IMPORT;
        }

        return $imports;
    }

    /**
     * @param array<int, array{column: string, method: string, related_model: string}> $relations
     * @return array<int, array{column: string, method: string, related_model: string}>
     */
    private function uniqueBelongsToRelations(array $relations): array
    {
        $unique = [];

        foreach ($relations as $relation) {
            $unique[$relation['method']] = $relation;
        }

        return array_values($unique);
    }

    /**
     * @param array<int, array{method: string, related_model: string}> $relations
     * @return array<int, array{method: string, related_model: string}>
     */
    private function uniqueBelongsToManyRelations(array $relations): array
    {
        $unique = [];

        foreach ($relations as $relation) {
            $unique[$relation['method']] = $relation;
        }

        return array_values($unique);
    }

    /**
     * @param array{column: string, method: string, related_model: string} $relation
     */
    private function formatBelongsToMethod(string $model, array $relation): string
    {
        return <<<PHP
    /**
     * @return BelongsTo<{$relation['related_model']}, {$model}>
     */
    public function {$relation['method']}(): BelongsTo
    {
        return \$this->belongsTo({$relation['related_model']}::class);
    }
PHP;
    }

    /**
     * @param array{method: string, related_model: string} $relation
     */
    private function formatBelongsToManyMethod(string $model, array $relation): string
    {
        return <<<PHP
    /**
     * @return BelongsToMany<{$relation['related_model']}, {$model}>
     */
    public function {$relation['method']}(): BelongsToMany
    {
        return \$this->belongsToMany({$relation['related_model']}::class);
    }
PHP;
    }

    private function belongsToMethodName(string $column, string $foreignTable): string
    {
        if (Str::endsWith($column, '_id')) {
            return Str::camel(Str::beforeLast($column, '_id'));
        }

        return Str::camel(Str::singular($foreignTable));
    }

    private function foreignKeyColumn(array $foreignKey): ?string
    {
        $columns = $foreignKey['columns'] ?? $foreignKey['local_columns'] ?? null;

        if (is_array($columns)) {
            return $columns[0] ?? null;
        }

        return is_string($columns) ? $columns : null;
    }

    private function foreignKeyTable(array $foreignKey): ?string
    {
        $table = $foreignKey['foreign_table'] ?? $foreignKey['foreignTable'] ?? $foreignKey['referenced_table'] ?? null;

        if ($table === null) {
            return null;
        }

        return Str::afterLast((string) $table, '.');
    }

    private function columnName(array $column): string
    {
        return (string) ($column['name'] ?? $column['column_name']);
    }

    /**
     * @param array<int, array<string, mixed>> $columns
     * @return array<int, string>
     */
    private function columnNames(array $columns): array
    {
        return array_map(fn(array $column): string => $this->columnName($column), $columns);
    }
}
