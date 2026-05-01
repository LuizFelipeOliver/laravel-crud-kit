<?php

namespace Example\LaravelCrudKit\Template;

use Illuminate\Support\Facades\File;

final class StubRenderer
{
    public function __construct(
        private StubResolver $resolver,
    ) {}

    public function render(string $stub, array $replacements): string
    {
        $content = File::get($this->resolver->resolve($stub));

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        return $content;
    }
}
