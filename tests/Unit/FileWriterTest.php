<?php

use Example\LaravelCrudKit\Filesystem\FileWriter;
use Example\LaravelCrudKit\Generators\FileDefinition;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->stub = 'tests/file-writer.stub';
    $this->stubPath = base_path('stubs/vendor/kraken/' . $this->stub);
    $this->generatedPath = sys_get_temp_dir() . '/kraken-tests/' . uniqid('writer_', true);

    File::ensureDirectoryExists(dirname($this->stubPath));
    File::ensureDirectoryExists($this->generatedPath);
});

afterEach(function (): void {
    File::delete($this->stubPath);
    File::deleteDirectory($this->generatedPath);
});

it('writes rendered stub content to a new file', function (): void {
    File::put($this->stubPath, 'class {{ name }} {}');

    $path = $this->generatedPath . '/app/Models/Post.php';
    $written = app(FileWriter::class)->write(new FileDefinition(
        stub: $this->stub,
        path: $path,
        replacements: [
            '{{ name }}' => 'Post',
        ],
    ));

    expect($written)->toBeTrue()
        ->and(File::exists($path))->toBeTrue()
        ->and(File::get($path))->toBe('class Post {}');
});

it('does not overwrite an existing file', function (): void {
    File::put($this->stubPath, 'new content');

    $path = $this->generatedPath . '/app/Models/Post.php';
    File::ensureDirectoryExists(dirname($path));
    File::put($path, 'existing content');

    $written = app(FileWriter::class)->write(new FileDefinition(
        stub: $this->stub,
        path: $path,
    ));

    expect($written)->toBeFalse()
        ->and(File::get($path))->toBe('existing content');
});

it('throws when it cannot write the file', function (): void {
    File::put($this->stubPath, 'content');

    $blockedPath = $this->generatedPath . '/blocked';
    File::put($blockedPath, 'not a directory');

    app(FileWriter::class)->write(new FileDefinition(
        stub: $this->stub,
        path: $blockedPath . '/Post.php',
    ));
})->throws(RuntimeException::class, 'Could not write file');
