<?php

use Example\LaravelCrudKit\Attributes\BelongsTo;
use Example\LaravelCrudKit\Attributes\BelongsToMany;
use Example\LaravelCrudKit\Attributes\Pivot;

it('reads belongs to relations from an anonymous class', function (): void {
    $migration = new
    #[BelongsTo(['payment', 'user'])]
    class {};

    $attribute = (new ReflectionClass($migration))
        ->getAttributes(BelongsTo::class)[0]
        ->newInstance();

    expect($attribute->relations)->toBe(['payment', 'user']);
});

it('accepts a single belongs to many relation', function (): void {
    $migration = new
    #[BelongsToMany('role')]
    class {};

    $attribute = (new ReflectionClass($migration))
        ->getAttributes(BelongsToMany::class)[0]
        ->newInstance();

    expect($attribute->relations)->toBe(['role']);
});

it('normalizes pivot relations', function (): void {
    $migration = new
    #[Pivot(['role', 'user', 'role', '', '  permission  '])]
    class {};

    $attribute = (new ReflectionClass($migration))
        ->getAttributes(Pivot::class)[0]
        ->newInstance();

    expect($attribute->relations)->toBe(['role', 'user', 'permission']);
});
