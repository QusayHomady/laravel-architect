<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use QusaiHomadi\LaravelArchitect\Console\Commands\Concerns\ResolvesStubs;

class MakeRepository extends Command
{
    use ResolvesStubs;

    protected $signature = 'make:repository {name : The name of the repository, e.g., User or UserRepository}
                            {--model= : The name of the associated Eloquent model (defaults to matching the repository name)}
                            {--all : Generate the Service + DTO + Action for the entity as well}
                            {--force : Overwrite the files if they already exist}';

    protected $description = 'Create a Repository class and its Interface (add --all to generate the complete module)';

    protected $aliases = ['make:repo', 'make:rep'];

    public function handle(): int
    {
        $cleanName = $this->cleanName($this->argument('name'));
        $normalizedName = str_replace('\\', '/', $cleanName);
        $parts = explode('/', $normalizedName);
        $entityName = array_pop($parts);
        $subNamespace = count($parts) > 0 ? '\\' . implode('\\', $parts) : '';
        $subPath = count($parts) > 0 ? '/' . implode('/', $parts) : '';

        // For model, if it has slashes, convert to backslashes
        $modelOption = $this->option('model');
        if ($modelOption) {
            $model = str_replace('/', '\\', $modelOption);
        } else {
            $model = $subNamespace ? trim($subNamespace, '\\') . '\\' . $entityName : $entityName;
        }

        $namespace = config('laravel-architect.repository.namespace', 'App\\Repositories') . $subNamespace;
        $interfaceNamespace = config('laravel-architect.repository.interface_namespace', 'App\\Repositories\\Contracts') . $subNamespace;
        $interfaceSuffix = config('laravel-architect.repository.interface_suffix', 'RepositoryInterface');
        $modelNamespace = config('laravel-architect.model.namespace', 'App\\Models') . "\\{$model}";

        $interfaceClass = "{$entityName}{$interfaceSuffix}";
        $repositoryClass = "{$entityName}Repository";

        // Prompt user for pattern type if running interactively
        $patternType = 'Interface';
        if ($this->input->isInteractive()) {
            $patternType = $this->choice(
                'Which repository pattern structure do you want to use?',
                ['Interface', 'Abstract Class'],
                0
            );
        }

        if ($patternType === 'Interface') {
            $relation = 'implements';
            $parentClass = $interfaceClass;
            $parentNamespace = "{$interfaceNamespace}\\{$interfaceClass}";

            // 1) Interface
            $importsContent = "use Illuminate\Container\Attributes\Bind;\nuse {$namespace}\\{$repositoryClass};";
            $attributesContent = "#[Bind({$repositoryClass}::class)]\n";

            $interfaceContent = $this->buildFromStub('repository.interface', [
                'namespace' => $interfaceNamespace,
                'interface' => $interfaceClass,
                'imports' => $importsContent,
                'attributes' => $attributesContent,
            ]);

            $this->writeFile(
                config('laravel-architect.repository.interface_path', app_path('Repositories/Contracts')) . "{$subPath}/{$interfaceClass}.php",
                $interfaceContent
            );
        } else {
            $abstractClass = "Abstract{$entityName}Repository";
            $relation = 'extends';
            $parentClass = $abstractClass;
            $parentNamespace = "{$interfaceNamespace}\\{$abstractClass}";

            // 1) Abstract class
            $abstractContent = $this->buildFromStub('repository.abstract', [
                'namespace' => $interfaceNamespace,
                'class' => $abstractClass,
            ]);

            $this->writeFile(
                config('laravel-architect.repository.interface_path', app_path('Repositories/Contracts')) . "{$subPath}/{$abstractClass}.php",
                $abstractContent
            );
        }

        // 2) Concrete Repository
        $repositoryContent = $this->buildFromStub('repository', [
            'namespace' => $namespace,
            'class' => $repositoryClass,
            'relation' => $relation,
            'interface' => $parentClass,
            'model' => basename(str_replace('\\', '/', $model)),
            'modelNamespace' => $modelNamespace,
            'interfaceNamespace' => $parentNamespace,
        ]);

        $this->writeFile(
            config('laravel-architect.repository.path', app_path('Repositories')) . "{$subPath}/{$repositoryClass}.php",
            $repositoryContent
        );

        if ($relation === 'implements') {
            $this->info("Attribute #[Bind] is placed on the interface. Automatic resolution is active in Laravel 11.");
            if (config('laravel-architect.repository.auto_bind', true)) {
                $this->comment("If you prefer manual binding, you can add this to AppServiceProvider:");
                $this->line("\$this->app->bind(\\{$interfaceNamespace}\\{$interfaceClass}::class, \\{$namespace}\\{$repositoryClass}::class);");
            }
        }

        // --all: generate rest of layers
        if ($this->option('all')) {
            $this->newLine();
            $this->call('make:service', [
                'name' => $cleanName,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:dto', [
                'name' => $cleanName,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:action', [
                'name' => $subPath ? trim($subPath, '/') . "/Create{$entityName}Action" : "Create{$entityName}Action",
                '--service' => "{$cleanName}Service",
                '--dto' => "{$cleanName}DTO",
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
