<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands\Concerns;

trait ResolvesStubs
{
    /**
     * Get the stub file path: checks the project directory first (if published and customized),
     * and falls back to the default package stubs.
     */
    protected function getStub(string $name): string
    {
        $published = base_path("stubs/vendor/laravel-architect/{$name}.stub");

        if (file_exists($published)) {
            return $published;
        }

        return __DIR__ . "/../../../../stubs/{$name}.stub";
    }

    protected function buildFromStub(string $stub, array $replacements): string
    {
        $content = file_get_contents($this->getStub($stub));

        foreach ($replacements as $search => $replace) {
            $content = str_replace('{{ ' . $search . ' }}', $replace, $content);
        }

        return $content;
    }

    protected function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);

        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $force = method_exists($this, 'option') && $this->option('force');

        if (file_exists($path) && !$force) {
            $this->warn("File already exists (use --force to overwrite): {$path}");
            return;
        }

        file_put_contents($path, $content);
        $this->info("✔ Created: {$path}");
    }
}
