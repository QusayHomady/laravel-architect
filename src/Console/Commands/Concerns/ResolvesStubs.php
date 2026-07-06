<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands\Concerns;

trait ResolvesStubs
{
    /**
     * يرجع مسار الـ stub: أول شي يدور بمسار المشروع (لو المستخدم عمل publish وعدّل عليه)
     * ولو ما لقاه يرجع للـ stub الأساسي بالباكج.
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
            $this->warn("الملف موجود مسبقاً (استخدم --force للاستبدال): {$path}");
            return;
        }

        file_put_contents($path, $content);
        $this->info("✔ تم إنشاء: {$path}");
    }
}
