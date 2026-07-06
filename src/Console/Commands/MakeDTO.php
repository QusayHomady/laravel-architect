<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use QusaiHomadi\LaravelArchitect\Console\Commands\Concerns\ResolvesStubs;

class MakeDTO extends Command
{
    use ResolvesStubs;

    protected $signature = 'make:dto {name : اسم الـ DTO, مثال: User أو UserDTO}
                            {--all : يولد معه أيضاً Repository + Service + Action لنفس الكيان}
                            {--force : استبدال الملفات لو موجودة}';

    protected $description = 'إنشاء DTO Class بسيط (أضف --all لتوليد الوحدة كاملة)';

    public function handle(): int
    {
        $name = $this->cleanName($this->argument('name'));
        $dtoClass = "{$name}DTO";

        $namespace = config('laravel-architect.dto.namespace', 'App\\DTOs');

        $content = $this->buildFromStub('dto', [
            'namespace' => $namespace,
            'class' => $dtoClass,
        ]);

        $this->writeFile(
            config('laravel-architect.dto.path', app_path('DTOs')) . "/{$dtoClass}.php",
            $content
        );

        if ($this->option('all')) {
            $this->newLine();
            $this->call('make:repository', [
                'name' => $name,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:service', [
                'name' => $name,
                '--repository' => $name,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:action', [
                'name' => "Create{$name}Action",
                '--service' => "{$name}Service",
                '--dto' => $dtoClass,
                '--force' => $this->option('force'),
            ]);
        }

        return self::SUCCESS;
    }

    protected function cleanName(string $name): string
    {
        return preg_replace('/DTO$/', '', $name);
    }
}
