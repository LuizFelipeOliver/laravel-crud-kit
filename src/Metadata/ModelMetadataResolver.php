<?php

namespace Example\LaravelCrudKit\Metadata;

use Illuminate\Support\Facades\Schema;

final class ModelMetadataResolver
{
    public function __construct(
        private ColumnCastResolver $castResolver,
        private RelationshipResolver $relationshipResolver,
    ) {}

    public function resolve(string $model, string $table): array
    {
        if (! Schema::hasTable($table)) {
            return $this->emptyMetadata($table);
        }

        $columns = Schema::getColumns($table);
        $columnNames = array_map(fn(array $column): string => $this->columnName($column), $columns);
        $usesSoftDeletes = in_array('deleted_at', $columnNames, true);
        $usesTimestamps = in_array('created_at', $columnNames, true) && in_array('updated_at', $columnNames, true);
        $fillable = $this->fillableColumns($columnNames);
        $casts = $this->casts($columns);
        $relationships = $this->relationshipResolver->resolve($model, $table, $columns);
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
            'repository_relations' => $this->formatRepositoryRelations($relationships['names']),
        ];
    }

    private function emptyMetadata(string $table): array
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
            'repository_relations' => '[]',
        ];
    }

    private function fillableColumns(array $columns): array
    {
        return array_values(array_filter($columns, fn(string $column): bool => ! in_array($column, [
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

            $cast = $this->castResolver->resolve($column);

            if ($cast !== null) {
                $casts[$name] = $cast;
            }
        }

        return $casts;
    }

    private function columnName(array $column): string
    {
        return (string) ($column['name'] ?? $column['column_name']);
    }

    private function formatImports(array $imports): string
    {
        sort($imports);

        return implode("\n", array_map(fn(string $import): string => "use {$import};", array_unique($imports)));
    }

    private function formatAttributes(string $table, bool $usesTimestamps, array $fillable): string
    {
        $attributes = [];

        if ($fillable !== []) {
            $attributes[] = '#[Fillable([' . implode(', ', array_map(
                fn(string $column): string => "'{$column}'",
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
            fn(string $column, string $cast): string => "            '{$column}' => '{$cast}',",
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

    private function formatRepositoryRelations(array $relations): string
    {
        if ($relations === []) {
            return '[]';
        }

        return '[' . implode(', ', array_map(
            fn(string $relation): string => "'{$relation}'",
            $relations
        )) . ']';
    }
}
