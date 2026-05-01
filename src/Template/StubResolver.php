<?php

namespace Example\LaravelCrudKit\Template;

use Illuminate\Support\Facades\File;

final class StubResolver
{
    public function resolve(string $stub): string
    {
        $publishedKrakenStub = base_path("stubs/vendor/kraken/{$stub}");

        if (File::exists($publishedKrakenStub)) {
            return $publishedKrakenStub;
        }

        return __DIR__ . "/../../stubs/{$stub}";
    }
}
