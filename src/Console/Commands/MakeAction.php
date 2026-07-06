<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use QusaiHomadi\LaravelArchitect\Console\Commands\Concerns\ResolvesStubs;

class MakeAction extends Command
{
    use ResolvesStubs;

    protected $signature = 'make:action {name : اسم الأكشن كامل, مثال: CreateUserAction}
                            {--service= : اسم السيرفس المرتبط (افتراضياً مستخرج من الاسم)}
                            {--dto= : اسم الـ DTO المرتبط (افتراضياً مستخرج من الاسم)}
                            {--all : يولد معه أيضاً Repository + Service + DTO لنفس الكيان}
                            {--force : استبدال الملفات لو موجودة}';

    protected $description = 'إنشاء Action Class (Single Action) يعتمد على Service و DTO (أضف --all لتوليد الوحدة كاملة)';

    public function handle(): int
    {
        $rawName = $this->argument('name');
        $actionClass = str_ends_with($rawName, 'Action') ? $rawName : "{$rawName}Action";

        // استخراج اسم الكيان من مثل CreateUserAction => User
        $base = str_replace('Action', '', $actionClass);
        $entity = preg_replace('/^(Create|Update|Delete|Store|Find)/', '', $base) ?: $base;

        $serviceClass = $this->option('service') ?: "{$entity}Service";
        $dtoClass = $this->option('dto') ?: "{$entity}DTO";

        $namespace = config('laravel-architect.action.namespace', 'App\\Actions');
        $serviceNamespace = config('laravel-architect.service.namespace', 'App\\Services') . "\\{$serviceClass}";
        $dtoNamespace = config('laravel-architect.dto.namespace', 'App\\DTOs') . "\\{$dtoClass}";

        $content = $this->buildFromStub('action', [
            'namespace' => $namespace,
            'class' => $actionClass,
            'service' => $serviceClass,
            'serviceNamespace' => $serviceNamespace,
            'dto' => $dtoClass,
            'dtoNamespace' => $dtoNamespace,
        ]);

        $this->writeFile(
            config('laravel-architect.action.path', app_path('Actions')) . "/{$actionClass}.php",
            $content
        );

        if ($this->option('all')) {
            $this->newLine();
            $this->call('make:repository', [
                'name' => $entity,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:service', [
                'name' => $entity,
                '--repository' => $entity,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:dto', [
                'name' => $entity,
                '--force' => $this->option('force'),
            ]);
        }

        return self::SUCCESS;
    }
}
