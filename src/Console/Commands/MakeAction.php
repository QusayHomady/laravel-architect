<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use QusaiHomadi\LaravelArchitect\Console\Commands\Concerns\ResolvesStubs;

class MakeAction extends Command
{
    use ResolvesStubs;

    protected $signature = 'make:action {name : The full name of the action class, e.g., CreateUserAction}
                            {--service= : The associated service class (defaults to matching the action entity)}
                            {--dto= : The associated DTO class (defaults to matching the action entity)}
                            {--all : Generate the Repository + Service + DTO for the entity as well}
                            {--force : Overwrite the files if they already exist}';

    protected $description = 'Create a Single Action Class that depends on a Service and DTO (add --all to generate the complete module)';

    public function handle(): int
    {
        $rawName = $this->argument('name');
        
        // Standardize slashes to forward slashes first to make parsing easier
        $normalizedName = str_replace('\\', '/', $rawName);
        $parts = explode('/', $normalizedName);
        $actionClassInput = array_pop($parts);
        $subNamespace = count($parts) > 0 ? '\\' . implode('\\', $parts) : '';
        $subPath = count($parts) > 0 ? '/' . implode('/', $parts) : '';

        $actionClass = str_ends_with($actionClassInput, 'Action') ? $actionClassInput : "{$actionClassInput}Action";

        // Extract entity name from action name: CreateUserAction -> User
        $base = str_replace('Action', '', $actionClass);
        $entityName = preg_replace('/^(Create|Update|Delete|Store|Find)/', '', $base) ?: $base;

        $serviceOption = $this->option('service');
        if ($serviceOption) {
            $cleanService = str_replace('/', '\\', str_replace('\\', '/', $serviceOption));
            $serviceParts = explode('\\', $cleanService);
            $serviceClass = array_pop($serviceParts);
            $serviceSubNamespace = count($serviceParts) > 0 ? '\\' . implode('\\', $serviceParts) : $subNamespace;
        } else {
            $serviceClass = "{$entityName}Service";
            $serviceSubNamespace = $subNamespace;
        }

        $dtoOption = $this->option('dto');
        if ($dtoOption) {
            $cleanDto = str_replace('/', '\\', str_replace('\\', '/', $dtoOption));
            $dtoParts = explode('\\', $cleanDto);
            $dtoClass = array_pop($dtoParts);
            $dtoSubNamespace = count($dtoParts) > 0 ? '\\' . implode('\\', $dtoParts) : $subNamespace;
        } else {
            $dtoClass = "{$entityName}DTO";
            $dtoSubNamespace = $subNamespace;
        }

        $namespace = config('laravel-architect.action.namespace', 'App\\Actions') . $subNamespace;
        $serviceNamespace = config('laravel-architect.service.namespace', 'App\\Services') . $serviceSubNamespace . "\\{$serviceClass}";
        $dtoNamespace = config('laravel-architect.dto.namespace', 'App\\DTOs') . $dtoSubNamespace . "\\{$dtoClass}";

        $content = $this->buildFromStub('action', [
            'namespace' => $namespace,
            'class' => $actionClass,
            'service' => $serviceClass,
            'serviceNamespace' => $serviceNamespace,
            'dto' => $dtoClass,
            'dtoNamespace' => $dtoNamespace,
        ]);

        $this->writeFile(
            config('laravel-architect.action.path', app_path('Actions')) . "{$subPath}/{$actionClass}.php",
            $content
        );

        if ($this->option('all')) {
            $this->newLine();
            $this->call('make:repository', [
                'name' => $subPath ? trim($subPath, '/') . "/{$entityName}" : $entityName,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:service', [
                'name' => $subPath ? trim($subPath, '/') . "/{$entityName}" : $entityName,
                '--repository' => $subPath ? trim($subPath, '/') . "/{$entityName}" : $entityName,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:dto', [
                'name' => $subPath ? trim($subPath, '/') . "/{$entityName}" : $entityName,
                '--force' => $this->option('force'),
            ]);
        }

        return self::SUCCESS;
    }
}
