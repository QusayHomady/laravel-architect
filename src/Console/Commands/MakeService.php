<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use QusaiHomadi\LaravelArchitect\Console\Commands\Concerns\ResolvesStubs;

class MakeService extends Command
{
    use ResolvesStubs;

    protected $signature = 'make:service {name : اسم السيرفس, مثال: User أو UserService}
                            {--repository= : اسم الـ Repository Interface المرتبط (افتراضياً نفس name)}
                            {--all : يولد معه أيضاً Repository + DTO + Action لنفس الكيان}
                            {--force : استبدال الملفات لو موجودة}';

    protected $description = 'إنشاء Service Class يعتمد على Repository Interface (أضف --all لتوليد الوحدة كاملة)';

    public function handle(): int
    {
        $name = $this->cleanName($this->argument('name'));
        $repositoryBase = $this->option('repository') ?: $name;

        $namespace = config('laravel-architect.service.namespace', 'App\\Services');
        $repositoryInterfaceNamespace = config('laravel-architect.repository.interface_namespace', 'App\\Repositories\\Contracts');
        $interfaceSuffix = config('laravel-architect.repository.interface_suffix', 'RepositoryInterface');
        $repositoryInterface = "{$repositoryBase}{$interfaceSuffix}";
        $serviceClass = "{$name}Service";

        $content = $this->buildFromStub('service', [
            'namespace' => $namespace,
            'class' => $serviceClass,
            'repositoryInterface' => $repositoryInterface,
            'repositoryInterfaceNamespace' => "{$repositoryInterfaceNamespace}\\{$repositoryInterface}",
        ]);

        $this->writeFile(
            config('laravel-architect.service.path', app_path('Services')) . "/{$serviceClass}.php",
            $content
        );

        if ($this->option('all')) {
            $this->newLine();
            $this->call('make:repository', [
                'name' => $name,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:dto', [
                'name' => $name,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:action', [
                'name' => "Create{$name}Action",
                '--service' => $serviceClass,
                '--dto' => "{$name}DTO",
                '--force' => $this->option('force'),
            ]);
        }

        return self::SUCCESS;
    }

    protected function cleanName(string $name): string
    {
        return preg_replace('/Service$/', '', $name);
    }
}
