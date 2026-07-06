<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use QusaiHomadi\LaravelArchitect\Console\Commands\Concerns\ResolvesStubs;

class MakeDTO extends Command
{
    use ResolvesStubs;

    protected $signature = 'make:dto {name : The name of the DTO, e.g., User or UserDTO}
                            {--all : Generate the Repository + Service + Action for the entity as well}
                            {--force : Overwrite the files if they already exist}';

    protected $description = 'Create a simple DTO Class (add --all to generate the complete module)';

    public function handle(): int
    {
        $cleanName = $this->cleanName($this->argument('name'));
        $normalizedName = str_replace('\\', '/', $cleanName);
        $parts = explode('/', $normalizedName);
        $entityName = array_pop($parts);
        $subNamespace = count($parts) > 0 ? '\\' . implode('\\', $parts) : '';
        $subPath = count($parts) > 0 ? '/' . implode('/', $parts) : '';

        $dtoClass = "{$entityName}DTO";
        $namespace = config('laravel-architect.dto.namespace', 'App\\DTOs') . $subNamespace;

        $content = $this->buildFromStub('dto', [
            'namespace' => $namespace,
            'class' => $dtoClass,
        ]);

        $this->writeFile(
            config('laravel-architect.dto.path', app_path('DTOs')) . "{$subPath}/{$dtoClass}.php",
            $content
        );

        if ($this->option('all')) {
            $this->newLine();
            $this->call('make:repository', [
                'name' => $cleanName,
                '--force' => $this->option('force'),
            ]);
            $this->call('make:service', [
                'name' => $cleanName,
                '--repository' => $cleanName,
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

    protected function cleanName(string $name): string
    {
        return preg_replace('/DTO$/', '', $name);
    }
}
