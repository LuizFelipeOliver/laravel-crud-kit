<?php

use Example\LaravelCrudKit\Metadata\MigrationRelationMetadataResolver;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->migrationPath = base_path('database/migrations');
    $this->migrationFiles = [];

    File::ensureDirectoryExists($this->migrationPath);
});

afterEach(function (): void {
    foreach ($this->migrationFiles as $migrationFile) {
        File::delete($migrationFile);
    }
});

it('returns empty metadata when create migration does not exist', function (): void {
    $metadata = app(MigrationRelationMetadataResolver::class)->resolve('orders');

    expect($metadata->hasRelations())->toBeFalse()
        ->and($metadata->belongsTo)->toBe([])
        ->and($metadata->pivot)->toBe([]);
});

it('reads relation attributes from a create migration', function (): void {
    $path = $this->migrationPath . '/2026_05_14_000000_create_orders_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\BelongsTo;

return new
#[BelongsTo(['payment', 'user'])]
class {};
PHP);

    $metadata = app(MigrationRelationMetadataResolver::class)->resolve('orders');

    expect($metadata->hasRelations())->toBeTrue()
        ->and($metadata->belongsTo)->toBe(['payment', 'user'])
        ->and($metadata->pivot)->toBe([]);
});

it('reads pivot attributes from a create migration', function (): void {
    $path = $this->migrationPath . '/2026_05_14_000000_create_role_user_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['role', 'user'])]
class {};
PHP);

    $metadata = app(MigrationRelationMetadataResolver::class)->resolve('role_user');

    expect($metadata->hasRelations())->toBeTrue()
        ->and($metadata->belongsTo)->toBe([])
        ->and($metadata->pivot)->toBe(['role', 'user']);
});

it('ignores migrations that are not create table migrations', function (): void {
    $path = $this->migrationPath . '/2026_05_14_000000_add_payment_to_orders_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\BelongsTo;

return new
#[BelongsTo('payment')]
class {};
PHP);

    $metadata = app(MigrationRelationMetadataResolver::class)->resolve('orders');

    expect($metadata->hasRelations())->toBeFalse();
});

it('ignores create migrations that do not return an object', function (): void {
    $path = $this->migrationPath . '/2026_05_14_000005_create_orders_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

return true;
PHP);

    $metadata = app(MigrationRelationMetadataResolver::class)->resolve('orders');

    expect($metadata->hasRelations())->toBeFalse();
});

it('lists valid pivot metadata from create migrations', function (): void {
    $path = $this->migrationPath . '/2026_05_14_000001_create_role_user_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['role', 'user'])]
class {};
PHP);

    $pivots = app(MigrationRelationMetadataResolver::class)->pivots();

    expect($pivots)->toBe([
        [
            'table' => 'role_user',
            'relations' => ['role', 'user'],
        ],
    ]);
});

it('does not list create migrations without pivot attributes', function (): void {
    $path = $this->migrationPath . '/2026_05_14_000002_create_orders_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\BelongsTo;

return new
#[BelongsTo('user')]
class {};
PHP);

    expect(app(MigrationRelationMetadataResolver::class)->pivots())->toBe([]);
});

it('does not list pivots with invalid relation count', function (): void {
    $path = $this->migrationPath . '/2026_05_14_000003_create_permission_role_user_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['permission', 'role', 'user'])]
class {};
PHP);

    expect(app(MigrationRelationMetadataResolver::class)->pivots())->toBe([]);
});

it('does not list pivots outside laravel table convention', function (): void {
    $path = $this->migrationPath . '/2026_05_14_000004_create_user_role_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['role', 'user'])]
class {};
PHP);

    expect(app(MigrationRelationMetadataResolver::class)->pivots())->toBe([]);
});
