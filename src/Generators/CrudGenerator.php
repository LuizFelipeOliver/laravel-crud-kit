<?php

namespace Example\LaravelCrudKit\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrudGenerator
{
    public function generate(string $name, ?string $table = null): void
    {
        $model = Str::studly($name);
        $variable = Str::camel($name);
        $plural = Str::plural(Str::kebab($name));
        $table ??= Str::snake(Str::pluralStudly($model));

        $namespaces = array_merge([
            'controllers' => 'App\\Http\\Controllers\\Api',
            'models' => 'App\\Models',
            'services' => 'App\\Services',
            'repositories' => 'App\\Repositories',
        ], config('crud-kit.namespaces', []));

        $modelMetadata = $this->modelMetadata($model, $table);

        $this->generateFromStub('model.stub', config('crud-kit.paths.models', app_path('Models')) . "/{$model}.php", [
            '{{ namespace }}' => $namespaces['models'],
            '{{ name }}' => $model,
            '{{ imports }}' => $modelMetadata['imports'],
            '{{ attributes }}' => $modelMetadata['attributes'],
            '{{ uses }}' => $modelMetadata['uses'],
            '{{ casts }}' => $modelMetadata['casts'],
            '{{ relationships }}' => $modelMetadata['relationships'],
        ]);

        $this->generateFromStub('service.stub', config('crud-kit.paths.services') . "/{$model}Service.php", [
            '{{ namespace }}' => $namespaces['services'],
            '{{ repository_namespace }}' => $namespaces['repositories'],
            '{{ name }}' => $model,
            '{{ model }}' => $model,
            '{{ variable }}' => $variable,
        ]);

        $this->generateFromStub('repository.stub', config('crud-kit.paths.repositories') . "/{$model}Repository.php", [
            '{{ namespace }}' => $namespaces['repositories'],
            '{{ model_namespace }}' => $namespaces['models'],
            '{{ name }}' => $model,
            '{{ model }}' => $model,
            '{{ variable }}' => $variable,
        ]);

        $this->generateFromStub('controller.stub', config('crud-kit.paths.controllers') . "/{$model}Controller.php", [
            '{{ namespace }}' => $namespaces['controllers'],
            '{{ service_namespace }}' => $namespaces['services'],
            '{{ name }}' => $model,
            '{{ model }}' => $model,
            '{{ variable }}' => $variable,
            '{{ route }}' => $plural,
        ]);
    }

    private function generateFromStub(string $stub, string $targetPath, array $replacements): void
    {
        $stubPath = $this->resolveStubPath($stub);

        $content = File::get($stubPath);

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        File::ensureDirectoryExists(dirname($targetPath));

        if (File::exists($targetPath)) {
            return;
        }

        File::put($targetPath, $content);
    }

    private function resolveStubPath(string $stub): string
    {
        $publishedStub = base_path("stubs/vendor/crud-kit/{$stub}");

        if (File::exists($publishedStub)) {
            return $publishedStub;
        }

        return __DIR__ . "/../../stubs/{$stub}";
    }

    private function modelMetadata(string $model, string $table): array
    {
        if (! Schema::hasTable($table)) {
            return $this->emptyModelMetadata($model, $table);
        }

        $columns = Schema::getColumns($table);
        $columnNames = array_map(fn (array $column): string => $this->columnName($column), $columns);
        $usesSoftDeletes = in_array('deleted_at', $columnNames, true);
        $usesTimestamps = in_array('created_at', $columnNames, true) && in_array('updated_at', $columnNames, true);
        $fillable = $this->fillableColumns($columnNames);
        $casts = $this->casts($columns);
        $relationships = $this->relationships($model, $table, $columns);
        $imports = [
            'Illuminate\\Database\\Eloquent\\Attributes\\Table',
            'Illuminate\\Database\\Eloquent\\Model',
        ];

        if ($fillable !== []) {
            $imports[] = 'Illuminate\\Database\\Eloquent\\Attributes\\Fillable';
        }

        if (! $usesTimestamps) {
            $imports[] = 'Illuminate\\Database\\Eloquent\\Attributes\\WithoutTimestamps';
        }

        if ($usesSoftDeletes) {
            $imports[] = 'Illuminate\\Database\\Eloquent\\SoftDeletes';
        }

        foreach ($relationships['imports'] as $import) {
            $imports[] = $import;
        }

        return [
            'imports' => $this->formatImports($imports),
            'attributes' => $this->formatAttributes($table, $usesTimestamps, $fillable),
            'uses' => $usesSoftDeletes ? '    use SoftDeletes;' : '',
            'casts' => $this->formatCasts($casts),
            'relationships' => $relationships['methods'],
        ];
    }

    private function emptyModelMetadata(string $model, string $table): array
    {
        return [
            'imports' => $this->formatImports([
                'Illuminate\\Database\\Eloquent\\Attributes\\Table',
                'Illuminate\\Database\\Eloquent\\Model',
            ]),
            'attributes' => $this->formatAttributes($table, true, []),
            'uses' => '',
            'casts' => $this->formatCasts([]),
            'relationships' => '',
        ];
    }

    private function fillableColumns(array $columns): array
    {
        return array_values(array_filter($columns, fn (string $column): bool => ! in_array($column, [
            'id',
            'created_at',
            'updated_at',
            'deleted_at',
            'remember_token',
        ], true)));
    }

    private function casts(array $columns): array
    {
        $casts = [];

        foreach ($columns as $column) {
            $name = $this->columnName($column);

            if (in_array($name, ['id', 'created_at', 'updated_at', 'deleted_at'], true)) {
                continue;
            }

            $cast = $this->castForColumn($column);

            if ($cast !== null) {
                $casts[$name] = $cast;
            }
        }

        return $casts;
    }

    private function castForColumn(array $column): ?string
    {
        $type = Str::lower((string) ($column['type_name'] ?? $column['type'] ?? ''));

        return match (true) {
            str_contains($type, 'bool') => 'boolean',
            str_contains($type, 'bigint'),
            str_contains($type, 'int'),
            str_contains($type, 'smallint'),
            str_contains($type, 'tinyint') => 'integer',
            str_contains($type, 'decimal') => 'decimal:2',
            str_contains($type, 'double'),
            str_contains($type, 'float'),
            str_contains($type, 'real') => 'float',
            str_contains($type, 'json') => 'array',
            str_contains($type, 'date') && ! str_contains($type, 'time') => 'date',
            str_contains($type, 'datetime'),
            str_contains($type, 'timestamp') => 'datetime',
            default => null,
        };
    }

    private function relationships(string $model, string $table, array $columns): array
    {
        $imports = [];
        $methods = [];
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
        ];
    }

    private function tables(): array
    {
        return array_values(array_filter(array_map(
            fn (array $table): ?string => $this->tableName($table),
            Schema::getTables()
        )));
    }

    private function pivotRelatedColumn(string $table, string $foreignKey, string $currentTable, array $foreignKeys): ?array
    {
        $pivotForeignKeys = array_values(array_filter(
            $foreignKeys,
            fn (array $key): bool => $this->foreignKeyColumn($key) !== null && $this->foreignKeyTable($key) !== null
        ));

        if (count($pivotForeignKeys) === 2) {
            $referencesCurrentTable = collect($pivotForeignKeys)->contains(
                fn (array $key): bool => $this->foreignKeyColumn($key) === $foreignKey
                    && $this->foreignKeyTable($key) === $currentTable
            );

            if ($referencesCurrentTable) {
                return collect($pivotForeignKeys)->first(
                    fn (array $key): bool => $this->foreignKeyTable($key) !== $currentTable
                );
            }
        }

        return $this->conventionalPivotRelatedColumn($table, $foreignKey);
    }

    private function conventionalPivotRelatedColumn(string $table, string $foreignKey): ?array
    {
        $columns = array_map(
            fn (array $column): string => $this->columnName($column),
            Schema::getColumns($table)
        );

        $foreignColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Str::endsWith($column, '_id')
        ));

        if (count($foreignColumns) !== 2 || ! in_array($foreignKey, $foreignColumns, true)) {
            return null;
        }

        $relatedColumn = collect($foreignColumns)->first(fn (string $column): bool => $column !== $foreignKey);

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

    private function formatImports(array $imports): string
    {
        sort($imports);

        return implode("\n", array_map(fn (string $import): string => "use {$import};", array_unique($imports)));
    }

    private function formatAttributes(string $table, bool $usesTimestamps, array $fillable): string
    {
        $attributes = [];

        if ($fillable !== []) {
            $attributes[] = '#[Fillable([' . implode(', ', array_map(
                fn (string $column): string => "'{$column}'",
                $fillable
            )) . '])]';
        }

        $attributes[] = "#[Table('{$table}')]";

        if (! $usesTimestamps) {
            $attributes[] = '#[WithoutTimestamps]';
        }

        return implode("\n", $attributes);
    }

    private function formatCasts(array $casts): string
    {
        if ($casts === []) {
            return <<<PHP
    protected function casts(): array
    {
        return [];
    }
PHP;
        }

        $items = implode("\n", array_map(
            fn (string $column, string $cast): string => "            '{$column}' => '{$cast}',",
            array_keys($casts),
            $casts
        ));

        return <<<PHP
    protected function casts(): array
    {
        return [
{$items}
        ];
    }
PHP;
    }
}
