<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use QusaiHomadi\LaravelArchitect\Console\Commands\Concerns\ResolvesStubs;

class MakeService extends Command
{
    use ResolvesStubs;

    protected $signature = 'make:service {name : The name of the service, e.g., User or UserService}
                            {--repository= : The associated Repository Interface name (defaults to matching the service name)}
                            {--all : Generate the Repository + DTO + Action for the entity as well}
                            {--force : Overwrite the files if they already exist}';

    protected $description = 'Create a Service Class that depends on a Repository Interface (add --all to generate the complete module)';

    public function handle(): int
    {
        $cleanName = $this->cleanName($this->argument('name'));
        $normalizedName = str_replace('\\', '/', $cleanName);
        $parts = explode('/', $normalizedName);
        $entityName = array_pop($parts);
        $subNamespace = count($parts) > 0 ? '\\' . implode('\\', $parts) : '';
        $subPath = count($parts) > 0 ? '/' . implode('/', $parts) : '';

        $repositoryBaseOption = $this->option('repository');
        if ($repositoryBaseOption) {
            $cleanRepo = $this->cleanName($repositoryBaseOption);
            $normalizedRepo = str_replace('\\', '/', $cleanRepo);
            $repoParts = explode('/', $normalizedRepo);
            $repoEntity = array_pop($repoParts);
            $repoSubNamespace = count($repoParts) > 0 ? '\\' . implode('\\', $repoParts) : '';
            $repoSubPath = count($repoParts) > 0 ? '/' . implode('/', $repoParts) : '';
        } else {
            $repoEntity = $entityName;
            $repoSubNamespace = $subNamespace;
            $repoSubPath = $subPath;
        }

        $namespace = config('laravel-architect.service.namespace', 'App\\Services') . $subNamespace;
        $repositoryInterfaceNamespace = config('laravel-architect.repository.interface_namespace', 'App\\Repositories\\Contracts') . $repoSubNamespace;
        
        $interfaceSuffix = config('laravel-architect.repository.interface_suffix', 'RepositoryInterface');
        $interfacePath = config('laravel-architect.repository.interface_path', app_path('Repositories/Contracts')) . "{$repoSubPath}/";

        $abstractName = "Abstract{$repoEntity}Repository";
        $interfaceName = "{$repoEntity}{$interfaceSuffix}";

        // Safely detect if Abstract repository class was generated, fallback to standard Interface name
        if (file_exists($interfacePath . $abstractName . '.php')) {
            $repositoryParent = $abstractName;
        } else {
            $repositoryParent = $interfaceName;
        }

        $serviceClass = "{$entityName}Service";

        $content = $this->buildFromStub('service', [
            'namespace' => $namespace,
            'class' => $serviceClass,
            'repositoryInterface' => $repositoryParent,
            'repositoryInterfaceNamespace' => "{$repositoryInterfaceNamespace}\\{$repositoryParent}",
        ]);

        $this->writeFile(
            config('laravel-architect.service.path', app_path('Services')) . "{$subPath}/{$serviceClass}.php",
            $content
        );

        if ($this->option('all')) {
            $this->newLine();
            $this->call('make:repository', [
                'name' => $cleanName,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:dto', [
                'name' => $cleanName,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:action', [
                'name' => $subPath ? trim($subPath, '/') . "/Create{$entityName}Action" : "Create{$entityName}Action",
                '--service' => $serviceClass,
                '--dto' => "{$entityName}DTO",
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
