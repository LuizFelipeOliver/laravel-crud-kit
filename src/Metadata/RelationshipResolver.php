<?php

namespace Example\LaravelCrudKit\Metadata;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

final class RelationshipResolver
{
    public function resolve(string $model, string $table, array $columns): array
    {
        $imports = [];
        $methods = [];
        $names = [];
        $foreignKeys = $this->foreignKeys($table);
        $belongsToColumns = [];

        foreach ($foreignKeys as $foreignKey) {
            $columnName = $this->foreignKeyColumn($foreignKey);
            $foreignTable = $this->foreignKeyTable($foreignKey);

            if ($columnName === null || $foreignTable === null) {
                continue;
            }

            $belongsToColumns[] = $columnName;
            $relatedModel = Str::studly(Str::singular($foreignTable));
            $method = $this->belongsToMethodName($columnName, $foreignTable);
            $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo';
            $names[] = $method;
            $methods[] = <<<PHP
    public function {$method}(): BelongsTo
    {
        return \$this->belongsTo({$relatedModel}::class);
    }
PHP;
        }

        foreach ($columns as $column) {
            $columnName = $this->columnName($column);

            if (! Str::endsWith($columnName, '_id') || in_array($columnName, $belongsToColumns, true)) {
                continue;
            }

            $relatedModel = Str::studly(Str::beforeLast($columnName, '_id'));
            $method = Str::camel(Str::beforeLast($columnName, '_id'));
            $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo';
            $names[] = $method;
            $methods[] = <<<PHP
    public function {$method}(): BelongsTo
    {
        return \$this->belongsTo({$relatedModel}::class);
    }
PHP;
        }

        $foreignKey = Str::snake($model) . '_id';

        foreach ($this->tables() as $relatedTable) {
            if ($relatedTable === $table) {
                continue;
            }

            $relatedForeignKeys = $this->foreignKeys($relatedTable);
            $referencingColumn = $this->referencingColumn($relatedForeignKeys, $table) ?? (
                Schema::hasColumn($relatedTable, $foreignKey) ? $foreignKey : null
            );

            if ($referencingColumn === null) {
                continue;
            }

            $pivotColumn = $this->pivotRelatedColumn($relatedTable, $referencingColumn, $table, $relatedForeignKeys);

            if ($pivotColumn !== null) {
                $pivotTable = $this->foreignKeyTable($pivotColumn) ?? Str::plural(Str::beforeLast($this->foreignKeyColumn($pivotColumn), '_id'));
                $relatedModel = Str::studly(Str::singular($pivotTable));
                $method = Str::camel(Str::plural($relatedModel));
                $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany';
                $names[] = $method;
                $methods[] = <<<PHP
    public function {$method}(): BelongsToMany
    {
        return \$this->belongsToMany({$relatedModel}::class, '{$relatedTable}');
    }
PHP;

                continue;
            }

            $relatedModel = Str::studly(Str::singular($relatedTable));
            $method = Str::camel(Str::plural($relatedModel));
            $imports[] = 'Illuminate\\Database\\Eloquent\\Relations\\HasMany';
            $names[] = $method;
            $methods[] = <<<PHP
    public function {$method}(): HasMany
    {
        return \$this->hasMany({$relatedModel}::class);
    }
PHP;
        }

        return [
            'imports' => array_values(array_unique($imports)),
            'methods' => implode("\n\n", array_values(array_unique($methods))),
            'names' => array_values(array_unique($names)),
        ];
    }

    private function tables(): array
    {
        return array_values(array_filter(array_map(
            fn(array $table): ?string => $this->tableName($table),
            Schema::getTables()
        )));
    }

    private function pivotRelatedColumn(string $table, string $foreignKey, string $currentTable, array $foreignKeys): ?array
    {
        $pivotForeignKeys = array_values(array_filter(
            $foreignKeys,
            fn(array $key): bool => $this->foreignKeyColumn($key) !== null && $this->foreignKeyTable($key) !== null
        ));

        if (count($pivotForeignKeys) === 2) {
            $referencesCurrentTable = collect($pivotForeignKeys)->contains(
                fn(array $key): bool => $this->foreignKeyColumn($key) === $foreignKey
                    && $this->foreignKeyTable($key) === $currentTable
            );

            if ($referencesCurrentTable) {
                return collect($pivotForeignKeys)->first(
                    fn(array $key): bool => $this->foreignKeyTable($key) !== $currentTable
                );
            }
        }

        return $this->conventionalPivotRelatedColumn($table, $foreignKey);
    }

    private function conventionalPivotRelatedColumn(string $table, string $foreignKey): ?array
    {
        $columns = array_map(
            fn(array $column): string => $this->columnName($column),
            Schema::getColumns($table)
        );

        $foreignColumns = array_values(array_filter(
            $columns,
            fn(string $column): bool => Str::endsWith($column, '_id')
        ));

        if (count($foreignColumns) !== 2 || ! in_array($foreignKey, $foreignColumns, true)) {
            return null;
        }

        $relatedColumn = collect($foreignColumns)->first(fn(string $column): bool => $column !== $foreignKey);

        return [
            'columns' => [$relatedColumn],
            'foreign_table' => Str::plural(Str::beforeLast($relatedColumn, '_id')),
        ];
    }

    private function foreignKeys(string $table): array
    {
        if (! method_exists(Schema::getFacadeRoot(), 'getForeignKeys')) {
            return [];
        }

        return Schema::getForeignKeys($table);
    }

    private function referencingColumn(array $foreignKeys, string $table): ?string
    {
        foreach ($foreignKeys as $foreignKey) {
            if ($this->foreignKeyTable($foreignKey) === $table) {
                return $this->foreignKeyColumn($foreignKey);
            }
        }

        return null;
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

    private function tableName(array $table): ?string
    {
        $name = $table['name'] ?? $table['table'] ?? $table['table_name'] ?? null;

        if ($name === null) {
            return null;
        }

        return Str::afterLast((string) $name, '.');
    }
}
