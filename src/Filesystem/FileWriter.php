<?php

namespace Example\LaravelCrudKit\Filesystem;

use Example\LaravelCrudKit\Generators\FileDefinition;
use Example\LaravelCrudKit\Template\StubRenderer;
use Illuminate\Support\Facades\File;
use RuntimeException;

final class FileWriter
{
    public function __construct(
        private StubRenderer $renderer,
    ) {}

    public function write(FileDefinition $file): bool
    {
        if (File::exists($file->path)) {
            return false;
        }

        File::ensureDirectoryExists(dirname($file->path));

        $bytes = File::put(
            $file->path,
            $this->renderer->render($file->stub, $file->replacements)
        );

        if ($bytes === false) {
            throw new RuntimeException("Could not write file [{$file->path}].");
        }

        return true;
    }
}
