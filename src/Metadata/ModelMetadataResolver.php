<?php

namespace Example\LaravelCrudKit\Metadata;

use Illuminate\Support\Facades\Schema;

final class ModelMetadataResolver
{
    private const BASE_IMPORTS = [
        'Illuminate\\Database\\Eloquent\\Attributes\\Table',
        'Illuminate\\Database\\Eloquent\\Model',
    ];

    private const FILLABLE_IMPORT = 'Illuminate\\Database\\Eloquent\\Attributes\\Fillable';

    private const WITHOUT_TIMESTAMPS_IMPORT = 'Illuminate\\Database\\Eloquent\\Attributes\\WithoutTimestamps';

    private const SOFT_DELETES_IMPORT = 'Illuminate\\Database\\Eloquent\\SoftDeletes';

    private const GUARDED_COLUMNS = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'remember_token',
    ];

    private const NON_CASTABLE_COLUMNS = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function __construct(
        private ColumnCastResolver $castResolver,
        private RelationshipResolver $relationshipResolver,
    ) {}

    /**
     * @return array<string, string>
     */
    public function resolve(string $model, string $table): array
    {
        if (! Schema::hasTable($table)) {
            return $this->emptyMetadata($table);
        }

        $columns = Schema::getColumns($table);
        $columnNames = array_map(fn(array $column): string => $this->columnName($column), $columns);
        $usesSoftDeletes = $this->usesSoftDeletes($columnNames);
        $usesTimestamps = $this->usesTimestamps($columnNames);
        $fillable = $this->fillableColumns($columnNames);
        $relationships = $this->relationshipResolver->resolve($model, $table, $columns);

        return [
            'imports' => $this->formatImports($this->imports(
                fillable: $fillable,
                usesTimestamps: $usesTimestamps,
                usesSoftDeletes: $usesSoftDeletes,
                relationshipImports: $relationships['imports'],
            )),
            'attributes' => $this->formatAttributes($table, $usesTimestamps, $fillable),
            'uses' => $usesSoftDeletes ? '    use SoftDeletes;' : '',
            'casts' => $this->formatCasts($this->casts($columns)),
            'relationships' => $relationships['methods'],
            'repository_relations' => $this->formatRepositoryRelations($relationships['names']),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function emptyMetadata(string $table): array
    {
        return [
            'imports' => $this->formatImports(self::BASE_IMPORTS),
            'attributes' => $this->formatAttributes($table, true, []),
            'uses' => '',
            'casts' => $this->formatCasts([]),
            'relationships' => '',
            'repository_relations' => '[]',
        ];
    }

    /**
     * @param array<int, string> $columns
     */
    private function usesSoftDeletes(array $columns): bool
    {
        return in_array('deleted_at', $columns, true);
    }

    /**
     * @param array<int, string> $columns
     */
    private function usesTimestamps(array $columns): bool
    {
        if (! in_array('created_at', $columns, true)) {
            return false;
        }

        return in_array('updated_at', $columns, true);
    }

    /**
     * @param array<int, string> $columns
     * @return array<int, string>
     */
    private function fillableColumns(array $columns): array
    {
        return array_values(array_filter(
            $columns,
            fn(string $column): bool => ! in_array($column, self::GUARDED_COLUMNS, true)
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $columns
     * @return array<string, string>
     */
    private function casts(array $columns): array
    {
        $casts = [];

        foreach ($columns as $column) {
            $name = $this->columnName($column);

            if (in_array($name, self::NON_CASTABLE_COLUMNS, true)) {
                continue;
            }

            $cast = $this->castResolver->resolve($column);

            if ($cast === null) {
                continue;
            }

            $casts[$name] = $cast;
        }

        return $casts;
    }

    /**
     * @param array<string, mixed> $column
     */
    private function columnName(array $column): string
    {
        return (string) ($column['name'] ?? $column['column_name']);
    }

    /**
     * @param array<int, string> $fillable
     * @param array<int, string> $relationshipImports
     * @return array<int, string>
     */
    private function imports(
        array $fillable,
        bool $usesTimestamps,
        bool $usesSoftDeletes,
        array $relationshipImports,
    ): array {
        $imports = self::BASE_IMPORTS;

        if ($fillable !== []) {
            $imports[] = self::FILLABLE_IMPORT;
        }

        if (! $usesTimestamps) {
            $imports[] = self::WITHOUT_TIMESTAMPS_IMPORT;
        }

        if ($usesSoftDeletes) {
            $imports[] = self::SOFT_DELETES_IMPORT;
        }

        return [...$imports, ...$relationshipImports];
    }

    /**
     * @param array<int, string> $imports
     */
    private function formatImports(array $imports): string
    {
        sort($imports);

        return implode("\n", array_map(fn(string $import): string => "use {$import};", array_unique($imports)));
    }

    /**
     * @param array<int, string> $fillable
     */
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

    /**
     * @param array<string, string> $casts
     */
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

    /**
     * @param array<int, string> $relations
     */
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
