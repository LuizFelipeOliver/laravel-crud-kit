<?php

namespace Example\LaravelCrudKit\Filesystem;

use Example\LaravelCrudKit\Generators\FileDefinition;
use Example\LaravelCrudKit\Template\StubRenderer;
use Illuminate\Support\Facades\File;

final class FileWriter
{
    public function __construct(
        private StubRenderer $renderer,
    ) {}

    public function write(FileDefinition $file): void
    {
        if (File::exists($file->path)) {
            return;
        }

        File::ensureDirectoryExists(dirname($file->path));

        File::put(
            $file->path,
            $this->renderer->render($file->stub, $file->replacements)
        );
    }
}
