<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use QusaiHomadi\LaravelArchitect\Console\Commands\Concerns\ResolvesStubs;

class MakeRepository extends Command
{
    use ResolvesStubs;

    protected $signature = 'make:repository {name : اسم الريبوزيتوري, مثال: User أو UserRepository}
                            {--model= : اسم الموديل المرتبط (افتراضياً نفس name)}
                            {--all : يولد معه أيضاً Service + DTO + Action لنفس الكيان}
                            {--force : استبدال الملفات لو موجودة}';

    protected $description = 'إنشاء Repository + Interface الخاص فيه (أضف --all لتوليد الوحدة كاملة)';

    public function handle(): int
    {
        $name = $this->cleanName($this->argument('name'));
        $model = $this->option('model') ?: $name;

        $namespace = config('laravel-architect.repository.namespace', 'App\\Repositories');
        $interfaceNamespace = config('laravel-architect.repository.interface_namespace', 'App\\Repositories\\Contracts');
        $interfaceSuffix = config('laravel-architect.repository.interface_suffix', 'RepositoryInterface');
        $modelNamespace = config('laravel-architect.model.namespace', 'App\\Models') . "\\{$model}";

        $interfaceClass = "{$name}{$interfaceSuffix}";
        $repositoryClass = "{$name}Repository";

        // 1) الواجهة (Interface)
        $interfaceContent = $this->buildFromStub('repository.interface', [
            'namespace' => $interfaceNamespace,
            'interface' => $interfaceClass,
        ]);

        $this->writeFile(
            config('laravel-architect.repository.interface_path', app_path('Repositories/Contracts')) . "/{$interfaceClass}.php",
            $interfaceContent
        );

        // 2) التنفيذ (Repository)
        $repositoryContent = $this->buildFromStub('repository', [
            'namespace' => $namespace,
            'class' => $repositoryClass,
            'interface' => $interfaceClass,
            'model' => $model,
            'modelNamespace' => $modelNamespace,
            'interfaceNamespace' => "{$interfaceNamespace}\\{$interfaceClass}",
        ]);

        $this->writeFile(
            config('laravel-architect.repository.path', app_path('Repositories')) . "/{$repositoryClass}.php",
            $repositoryContent
        );

        if (config('laravel-architect.repository.auto_bind', true)) {
            $this->comment("لا تنسَ ربط الـ Interface بالـ Repository في AppServiceProvider:");
            $this->line("\$this->app->bind(\\{$interfaceNamespace}\\{$interfaceClass}::class, \\{$namespace}\\{$repositoryClass}::class);");
        }

        // --all: نكمّل توليد باقي الطبقات لنفس الكيان
        if ($this->option('all')) {
            $this->newLine();
            $this->call('make:service', [
                'name' => $name,
                '--repository' => $name,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:dto', [
                'name' => $name,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:action', [
                'name' => "Create{$name}Action",
                '--service' => "{$name}Service",
                '--dto' => "{$name}DTO",
                '--force' => $this->option('force'),
            ]);
        }

        return self::SUCCESS;
    }

    /**
     * ينضف الاسم: يقبل "User" أو "UserRepository" وينتج "User".
     */
    protected function cleanName(string $name): string
    {
        return preg_replace('/Repository$/', '', $name);
    }
}
