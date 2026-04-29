<?php

namespace Acme\CrudKit\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudGenerator
{
    public function generate(string $name): void
    {
        $model = Str::studly($name);
        $variable = Str::camel($name);
        $plural = Str::plural(Str::kebab($name));

        $this->generateFromStub('service.stub', config('crud-kit.paths.services') . "/{$model}Service.php", [
            '{{ name }}' => $model,
            '{{ model }}' => $model,
            '{{ variable }}' => $variable,
        ]);

        $this->generateFromStub('repository.stub', config('crud-kit.paths.repositories') . "/{$model}Repository.php", [
            '{{ name }}' => $model,
            '{{ model }}' => $model,
            '{{ variable }}' => $variable,
        ]);

        $this->generateFromStub('controller.stub', config('crud-kit.paths.controllers') . "/{$model}Controller.php", [
            '{{ name }}' => $model,
            '{{ model }}' => $model,
            '{{ variable }}' => $variable,
            '{{ route }}' => $plural,
        ]);
    }

    private function generateFromStub(string $stub, string $targetPath, array $replacements): void
    {
        $stubPath = $this->resolveStubPath($stub);

        $content = File::get($stubPath);

        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }

        File::ensureDirectoryExists(dirname($targetPath));

        if (File::exists($targetPath)) {
            return;
        }

        File::put($targetPath, $content);
    }

    private function resolveStubPath(string $stub): string
    {
        $publishedStub = base_path("stubs/vendor/crud-kit/{$stub}");

        if (File::exists($publishedStub)) {
            return $publishedStub;
        }

        return __DIR__ . "/../../stubs/{$stub}";
    }
}
