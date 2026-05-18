<?php

namespace Example\LaravelCrudKit\Filesystem;

use Example\LaravelCrudKit\Generators\FileDefinition;
use Example\LaravelCrudKit\Template\StubRenderer;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

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

        try {
            File::ensureDirectoryExists(dirname($file->path));

            $bytes = File::put(
                $file->path,
                $this->renderer->render($file->stub, $file->replacements)
            );
        } catch (Throwable $exception) {
            throw new RuntimeException("Could not write file [{$file->path}].", previous: $exception);
        }

        if ($bytes === false) {
            throw new RuntimeException("Could not write file [{$file->path}].");
        }

        return true;
    }
}
