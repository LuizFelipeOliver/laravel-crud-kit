<?php

namespace Example\LaravelCrudKit\Metadata;

use Illuminate\Support\Str;

final class ColumnCastResolver
{
    public function resolve(array $column): ?string
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
}
