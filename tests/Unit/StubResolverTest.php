<?php

use Example\LaravelCrudKit\Template\StubResolver;
use Illuminate\Support\Facades\File;

beforeEach(function (): void {
    $this->publishedStub = 'tests/resolver.stub';
    $this->publishedStubPath = base_path('stubs/vendor/kraken/' . $this->publishedStub);

    File::ensureDirectoryExists(dirname($this->publishedStubPath));
});

afterEach(function (): void {
    File::delete($this->publishedStubPath);
    File::deleteDirectory(base_path('stubs/vendor/kraken'));
});

it('resolves published kraken stubs before internal stubs', function (): void {
    File::put($this->publishedStubPath, 'published');

    expect(app(StubResolver::class)->resolve($this->publishedStub))->toBe($this->publishedStubPath);
});

it('falls back to internal stubs', function (): void {
    $path = app(StubResolver::class)->resolve('shared/model.stub');

    expect(realpath($path))->toBe(realpath(__DIR__ . '/../../stubs/shared/model.stub'));
});
