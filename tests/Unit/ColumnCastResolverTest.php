<?php

use Example\LaravelCrudKit\Metadata\ColumnCastResolver;

it('resolves database column types to model casts', function (array $column, ?string $expected): void {
    expect(app(ColumnCastResolver::class)->resolve($column))->toBe($expected);
})->with([
    'boolean type_name' => [['type_name' => 'boolean'], 'boolean'],
    'bool type_name' => [['type_name' => 'bool'], 'boolean'],
    'big integer type_name' => [['type_name' => 'bigint'], 'integer'],
    'integer type_name' => [['type_name' => 'integer'], 'integer'],
    'small integer type_name' => [['type_name' => 'smallint'], 'integer'],
    'tiny integer type_name' => [['type_name' => 'tinyint'], 'integer'],
    'decimal type_name' => [['type_name' => 'decimal'], 'decimal:2'],
    'double type_name' => [['type_name' => 'double'], 'float'],
    'float type_name' => [['type_name' => 'float'], 'float'],
    'real type_name' => [['type_name' => 'real'], 'float'],
    'json type_name' => [['type_name' => 'json'], 'array'],
    'jsonb type_name' => [['type_name' => 'jsonb'], 'array'],
    'date type_name' => [['type_name' => 'date'], 'date'],
    'datetime type_name' => [['type_name' => 'datetime'], 'datetime'],
    'timestamp type_name' => [['type_name' => 'timestamp'], 'datetime'],
    'varchar type_name' => [['type_name' => 'varchar'], null],
    'text type_name' => [['type_name' => 'text'], null],
    'empty type_name' => [['type_name' => ''], null],
    'missing type metadata' => [[], null],
]);

it('uses type when type name is missing', function (): void {
    expect(app(ColumnCastResolver::class)->resolve(['type' => 'integer']))->toBe('integer')
        ->and(app(ColumnCastResolver::class)->resolve(['type' => 'varchar']))->toBeNull();
});

it('normalizes mixed case database types', function (): void {
    expect(app(ColumnCastResolver::class)->resolve(['type_name' => 'TIMESTAMP']))->toBe('datetime')
        ->and(app(ColumnCastResolver::class)->resolve(['type_name' => 'Boolean']))->toBe('boolean');
});
