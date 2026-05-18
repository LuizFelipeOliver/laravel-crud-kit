<?php

use Example\LaravelCrudKit\Template\StubRenderer;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->stub = 'tests/renderer.stub';
    $this->stubPath = base_path('stubs/vendor/kraken/' . $this->stub);

    File::ensureDirectoryExists(dirname($this->stubPath));
});

afterEach(function (): void {
    File::delete($this->stubPath);
});

it('replaces placeholders in a stub', function (): void {
    File::put($this->stubPath, 'Hello {{ name }} from {{ namespace }}.');

    $content = app(StubRenderer::class)->render($this->stub, [
        '{{ name }}' => 'Post',
        '{{ namespace }}' => 'App\\Models',
    ]);

    expect($content)->toBe('Hello Post from App\\Models.');
});

it('replaces repeated placeholders', function (): void {
    File::put($this->stubPath, '{{ name }} uses {{ name }}Repository.');

    $content = app(StubRenderer::class)->render($this->stub, [
        '{{ name }}' => 'User',
    ]);

    expect($content)->toBe('User uses UserRepository.');
});

it('keeps placeholders without replacements', function (): void {
    File::put($this->stubPath, '{{ name }} has {{ missing }}.');

    $content = app(StubRenderer::class)->render($this->stub, [
        '{{ name }}' => 'Category',
    ]);

    expect($content)->toBe('Category has {{ missing }}.');
});
