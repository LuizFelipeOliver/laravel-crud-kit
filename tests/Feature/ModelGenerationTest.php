<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    $this->generatedPath = sys_get_temp_dir() . '/kraken-tests/' . uniqid('generation_', true);
    $this->migrationPath = base_path('database/migrations');
    $this->migrationFiles = [];

    config()->set('generator.paths', [
        'api_controller' => $this->generatedPath . '/app/Http/Controllers/Api',
        'web_controller' => $this->generatedPath . '/app/Http/Controllers',
        'models' => $this->generatedPath . '/app/Models',
        'services' => $this->generatedPath . '/app/Services',
        'repositories' => $this->generatedPath . '/app/Repositories',
        'factories' => $this->generatedPath . '/database/factories',
        'api_tests' => $this->generatedPath . '/tests/Feature/Api',
        'web_tests' => $this->generatedPath . '/tests/Feature/Web',
        'api_routes' => $this->generatedPath . '/routes/api.php',
        'web_routes' => $this->generatedPath . '/routes/web.php',
    ]);

    config()->set('generator.namespaces', [
        'api_controller' => 'App\\Http\\Controllers\\Api',
        'web_controller' => 'App\\Http\\Controllers',
        'models' => 'App\\Models',
        'services' => 'App\\Services',
        'repositories' => 'App\\Repositories',
        'factories' => 'Database\\Factories',
    ]);

    Schema::shouldReceive('hasTable')
        ->byDefault()
        ->andReturnUsing(fn(string $table): bool => in_array($table, [
            'users',
            'roles',
            'posts',
            'categories',
            'tags',
            'post_tag',
            'role_user',
            'role_user_missing_fk',
            'system_users',
            'user_role',
        ], true));

    Schema::shouldReceive('getTables')
        ->byDefault()
        ->andReturn([
            ['name' => 'users'],
            ['name' => 'roles'],
            ['name' => 'posts'],
            ['name' => 'categories'],
            ['name' => 'tags'],
            ['name' => 'post_tag'],
            ['name' => 'role_user'],
            ['name' => 'user_role'],
            ['name' => 'system_users'],
        ]);

    Schema::shouldReceive('getColumns')
        ->byDefault()
        ->andReturnUsing(fn(string $table): array => match ($table) {
            'users' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'name', 'type_name' => 'varchar'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            'roles' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'name', 'type_name' => 'varchar'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            'posts' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'user_id', 'type_name' => 'bigint'],
                ['name' => 'category_id', 'type_name' => 'bigint'],
                ['name' => 'title', 'type_name' => 'varchar'],
                ['name' => 'content', 'type_name' => 'text'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            'categories' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'user_id', 'type_name' => 'bigint'],
                ['name' => 'name', 'type_name' => 'varchar'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            'tags' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'name', 'type_name' => 'varchar'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            'post_tag' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'post_id', 'type_name' => 'bigint'],
                ['name' => 'tag_id', 'type_name' => 'bigint'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            'role_user' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'role_id', 'type_name' => 'bigint'],
                ['name' => 'user_id', 'type_name' => 'bigint'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            'role_user_missing_fk' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'role_id', 'type_name' => 'bigint'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            'user_role' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'role_id', 'type_name' => 'bigint'],
                ['name' => 'user_id', 'type_name' => 'bigint'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            'system_users' => [
                ['name' => 'id', 'type_name' => 'bigint'],
                ['name' => 'email', 'type_name' => 'varchar'],
                ['name' => 'is_active', 'type_name' => 'boolean'],
                ['name' => 'created_at', 'type_name' => 'timestamp'],
                ['name' => 'updated_at', 'type_name' => 'timestamp'],
            ],
            default => [],
        });

    Schema::shouldReceive('hasColumn')
        ->byDefault()
        ->andReturnUsing(fn(string $table, string $column): bool => match ($table) {
            'posts' => in_array($column, ['user_id', 'category_id'], true),
            'categories' => $column === 'user_id',
            'post_tag' => in_array($column, ['post_id', 'tag_id'], true),
            'role_user' => in_array($column, ['role_id', 'user_id'], true),
            'user_role' => in_array($column, ['role_id', 'user_id'], true),
            default => false,
        });

    Schema::shouldReceive('getForeignKeys')
        ->byDefault()
        ->andReturnUsing(fn(string $table): array => match ($table) {
            'posts' => [
                [
                    'columns' => ['user_id'],
                    'foreign_table' => 'users',
                ],
                [
                    'columns' => ['category_id'],
                    'foreign_table' => 'categories',
                ],
            ],
            'categories' => [
                [
                    'columns' => ['user_id'],
                    'foreign_table' => 'users',
                ],
            ],
            'post_tag' => [
                [
                    'columns' => ['post_id'],
                    'foreign_table' => 'posts',
                ],
                [
                    'columns' => ['tag_id'],
                    'foreign_table' => 'tags',
                ],
            ],
            default => [],
        });
});

afterEach(function (): void {
    foreach ($this->migrationFiles as $migrationFile) {
        File::delete($migrationFile);
    }

    File::deleteDirectory($this->generatedPath);
});

it('generates a belongs to relationship only once', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/Post.php');

    expect(substr_count($content, 'public function user(): BelongsTo'))->toBe(1)
        ->and(substr_count($content, 'use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;'))->toBe(1);
});

it('does not generate inverse relationships by default', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'User',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/User.php');

    expect($content)->not->toContain('public function posts(): HasMany')
        ->and($content)->not->toContain('public function categories(): HasMany')
        ->and($content)->not->toContain('use Illuminate\\Database\\Eloquent\\Relations\\HasMany;');
});

it('does not generate belongs to many relationships by default', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'User',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/User.php');

    expect($content)->not->toContain('BelongsToMany')
        ->and($content)->not->toContain('belongsToMany');
});

it('does not generate pivot relationships by default', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/Post.php');

    expect($content)->not->toContain('public function tags(): BelongsToMany')
        ->and($content)->not->toContain('belongsToMany');
});

it('does not append to an existing generated model', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'model',
    ])->assertSuccessful();

    $path = $this->generatedPath . '/app/Models/Post.php';
    $firstContent = File::get($path);

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'model',
    ])->assertSuccessful();

    $secondContent = File::get($path);

    expect($secondContent)->toBe($firstContent)
        ->and(substr_count($secondContent, 'class Post extends Model'))->toBe(1);
});

it('generates an api controller by default', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
    ])->assertSuccessful();

    $path = $this->generatedPath . '/app/Http/Controllers/Api/PostController.php';

    expect(File::exists($path))->toBeTrue()
        ->and(File::get($path))->toContain('namespace App\\Http\\Controllers\\Api;');
});

it('generates an api route with the controller by default', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/routes/api.php');

    expect($content)->toContain('\Illuminate\Support\Facades\Route::apiResource(\'posts\', \App\Http\Controllers\Api\PostController::class);');
});

it('does not duplicate an existing generated route', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
    ])->assertSuccessful();

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/routes/api.php');

    expect(substr_count($content, '\Illuminate\Support\Facades\Route::apiResource(\'posts\', \App\Http\Controllers\Api\PostController::class);'))->toBe(1);
});

it('generates only the requested file type', function (string $only, string $expectedPath): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => $only,
    ])->assertSuccessful();

    expect(File::exists($this->generatedPath . $expectedPath))->toBeTrue()
        ->and(File::exists($this->generatedPath . '/app/Models/Post.php'))->toBe($only === 'model')
        ->and(File::exists($this->generatedPath . '/app/Repositories/PostRepository.php'))->toBe($only === 'repository')
        ->and(File::exists($this->generatedPath . '/app/Services/PostService.php'))->toBe($only === 'service')
        ->and(File::exists($this->generatedPath . '/app/Http/Controllers/Api/PostController.php'))->toBe($only === 'controller');
})->with([
    'model' => ['model', '/app/Models/Post.php'],
    'repository' => ['repository', '/app/Repositories/PostRepository.php'],
    'service' => ['service', '/app/Services/PostService.php'],
    'controller' => ['controller', '/app/Http/Controllers/Api/PostController.php'],
]);

it('does not generate factory or feature test by default', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
    ])->assertSuccessful();

    expect(File::exists($this->generatedPath . '/database/factories/PostFactory.php'))->toBeFalse()
        ->and(File::exists($this->generatedPath . '/tests/Feature/Api/PostControllerTest.php'))->toBeFalse();
});

it('generates a factory and api feature test when requested', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--test' => true,
    ])->assertSuccessful();

    $factory = File::get($this->generatedPath . '/database/factories/PostFactory.php');
    $test = File::get($this->generatedPath . '/tests/Feature/Api/PostControllerTest.php');
    $model = File::get($this->generatedPath . '/app/Models/Post.php');

    expect($factory)->toContain('namespace Database\\Factories;')
        ->and($factory)->toContain('class PostFactory extends Factory')
        ->and($test)->toContain("route('posts.index')")
        ->and($test)->toContain('getJson')
        ->and($model)->toContain('use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;')
        ->and($model)->toContain('use HasFactory;');
});

it('generates all files when only is omitted', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
    ])->assertSuccessful();

    expect(File::exists($this->generatedPath . '/app/Models/Post.php'))->toBeTrue()
        ->and(File::exists($this->generatedPath . '/app/Repositories/PostRepository.php'))->toBeTrue()
        ->and(File::exists($this->generatedPath . '/app/Services/PostService.php'))->toBeTrue()
        ->and(File::exists($this->generatedPath . '/app/Http/Controllers/Api/PostController.php'))->toBeTrue();
});

it('generates a repository with resolved relations when requested', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'repository',
        '--repository' => 'relations',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Repositories/PostRepository.php');

    expect($content)->toContain("->with(['user', 'category'])");
});

it('uses a custom table when requested', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'User',
        '--only' => 'model',
        '--table' => 'system_users',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/User.php');

    expect($content)->toContain("#[Table('system_users')]")
        ->and($content)->toContain("#[Fillable(['email', 'is_active'])]")
        ->and($content)->toContain("'is_active' => 'boolean'");
});

it('reports created file paths and elapsed time', function (): void {
    $path = $this->generatedPath . '/app/Http/Controllers/Api/PostController.php';

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
    ])
        ->expectsOutputToContain('Created files:')
        ->expectsOutputToContain($path)
        ->expectsOutputToContain('Completed in ')
        ->assertSuccessful();
});

it('reports skipped existing file paths', function (): void {
    $path = $this->generatedPath . '/app/Http/Controllers/Api/PostController.php';

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
    ])->assertSuccessful();

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
    ])
        ->expectsOutputToContain('Skipped existing files:')
        ->expectsOutputToContain($path)
        ->assertSuccessful();
});

it('generates a web controller when requested', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
        '--web' => true,
    ])->assertSuccessful();

    $path = $this->generatedPath . '/app/Http/Controllers/PostController.php';

    expect(File::exists($path))->toBeTrue()
        ->and(File::get($path))->toContain('namespace App\\Http\\Controllers;');
});

it('generates a web route and web feature test when requested', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--web' => true,
        '--test' => true,
    ])->assertSuccessful();

    $route = File::get($this->generatedPath . '/routes/web.php');
    $test = File::get($this->generatedPath . '/tests/Feature/Web/PostControllerTest.php');

    expect($route)->toContain('\Illuminate\Support\Facades\Route::resource(\'posts\', \App\Http\Controllers\PostController::class)->only([\'index\', \'show\']);')
        ->and($test)->toContain("route('posts.index')")
        ->and($test)->toContain('renders the posts index page');
});

it('rejects conflicting blueprint options', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--api' => true,
        '--web' => true,
    ])
        ->expectsOutputToContain('Use only one blueprint option: --api or --web.')
        ->assertFailed();
});

it('rejects invalid only option', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'migration',
    ])
        ->expectsOutputToContain('Invalid --only value. Use: model, controller, service or repository.')
        ->assertFailed();
});

it('rejects invalid repository option', function (): void {
    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--repository' => 'eloquent',
    ])
        ->expectsOutputToContain('Invalid --repository value. Use: simple or relations.')
        ->assertFailed();
});

it('reports generation errors', function (): void {
    config()->set('generator.default_blueprint', 'admin');

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
    ])
        ->expectsOutputToContain('Kraken could not generate files: Blueprint [admin] is not supported.')
        ->assertFailed();
});

it('reports write errors thrown during generation', function (): void {
    $blockedPath = $this->generatedPath . '/blocked';

    File::ensureDirectoryExists($this->generatedPath);
    File::put($blockedPath, 'not a directory');

    config()->set('generator.paths.api_controller', $blockedPath . '/Api');

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'controller',
    ])
        ->expectsOutputToContain('Kraken could not generate files:')
        ->assertFailed();
});

it('uses migration belongs to attributes when they exist', function (): void {
    File::ensureDirectoryExists($this->migrationPath);

    $path = $this->migrationPath . '/2026_05_14_000001_create_posts_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\BelongsTo;

return new
#[BelongsTo(['user'])]
class {};
PHP);

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/Post.php');

    expect($content)->toContain('public function user(): BelongsTo')
        ->and($content)->not->toContain('public function category(): BelongsTo');
});

it('skips declared belongs to attributes without a matching foreign key column', function (): void {
    File::ensureDirectoryExists($this->migrationPath);

    $path = $this->migrationPath . '/2026_05_14_000002_create_posts_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\BelongsTo;

return new
#[BelongsTo(['payment'])]
class {};
PHP);

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/Post.php');

    expect($content)->not->toContain('public function payment(): BelongsTo')
        ->and($content)->not->toContain('public function user(): BelongsTo')
        ->and($content)->not->toContain('public function category(): BelongsTo');
});

it('generates belongs to many from a pivot attribute for the first related model', function (): void {
    File::ensureDirectoryExists($this->migrationPath);

    $path = $this->migrationPath . '/2026_05_14_000003_create_role_user_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['role', 'user'])]
class {};
PHP);

    $this->artisan('kraken:make', [
        'name' => 'User',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/User.php');

    expect($content)->toContain('use Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany;')
        ->and($content)->toContain('public function roles(): BelongsToMany')
        ->and($content)->toContain('return $this->belongsToMany(Role::class);');
});

it('generates belongs to many from a pivot attribute for the second related model', function (): void {
    File::ensureDirectoryExists($this->migrationPath);

    $path = $this->migrationPath . '/2026_05_14_000004_create_role_user_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['role', 'user'])]
class {};
PHP);

    $this->artisan('kraken:make', [
        'name' => 'Role',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/Role.php');

    expect($content)->toContain('use Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany;')
        ->and($content)->toContain('public function users(): BelongsToMany')
        ->and($content)->toContain('return $this->belongsToMany(User::class);');
});

it('does not generate belongs to many when pivot has invalid relation count', function (): void {
    File::ensureDirectoryExists($this->migrationPath);

    $path = $this->migrationPath . '/2026_05_14_000005_create_permission_role_user_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['permission', 'role', 'user'])]
class {};
PHP);

    $this->artisan('kraken:make', [
        'name' => 'User',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/User.php');

    expect($content)->not->toContain('BelongsToMany')
        ->and($content)->not->toContain('belongsToMany');
});

it('does not generate belongs to many when pivot foreign keys are missing', function (): void {
    File::ensureDirectoryExists($this->migrationPath);

    $path = $this->migrationPath . '/2026_05_14_000006_create_role_user_missing_fk_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['role', 'user_missing_fk'])]
class {};
PHP);

    $this->artisan('kraken:make', [
        'name' => 'UserMissingFk',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/UserMissingFk.php');

    expect($content)->not->toContain('BelongsToMany')
        ->and($content)->not->toContain('belongsToMany');
});

it('does not generate belongs to many for pivots outside laravel convention', function (): void {
    File::ensureDirectoryExists($this->migrationPath);

    $path = $this->migrationPath . '/2026_05_14_000007_create_user_role_table.php';
    $this->migrationFiles[] = $path;

    File::put($path, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['role', 'user'])]
class {};
PHP);

    $this->artisan('kraken:make', [
        'name' => 'User',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/User.php');

    expect($content)->not->toContain('BelongsToMany')
        ->and($content)->not->toContain('belongsToMany');
});

it('generates belongs to and pivot belongs to many together', function (): void {
    File::ensureDirectoryExists($this->migrationPath);

    $postsPath = $this->migrationPath . '/2026_05_14_000008_create_posts_table.php';
    $pivotPath = $this->migrationPath . '/2026_05_14_000009_create_post_tag_table.php';
    $this->migrationFiles[] = $postsPath;
    $this->migrationFiles[] = $pivotPath;

    File::put($postsPath, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\BelongsTo;

return new
#[BelongsTo(['user'])]
class {};
PHP);

    File::put($pivotPath, <<<'PHP'
<?php

use Example\LaravelCrudKit\Attributes\Pivot;

return new
#[Pivot(['post', 'tag'])]
class {};
PHP);

    $this->artisan('kraken:make', [
        'name' => 'Post',
        '--only' => 'model',
    ])->assertSuccessful();

    $content = File::get($this->generatedPath . '/app/Models/Post.php');

    expect($content)->toContain('use Illuminate\\Database\\Eloquent\\Relations\\BelongsTo;')
        ->and($content)->toContain('use Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany;')
        ->and($content)->toContain('public function user(): BelongsTo')
        ->and($content)->toContain('public function tags(): BelongsToMany');
});
