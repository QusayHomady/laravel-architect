<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;

class MakeModule extends Command
{
    protected $signature = 'make:module {name : The name of the entity, e.g., User}
                            {--force : Overwrite the files if they already exist}';

    protected $description = 'Generate a complete module (Repository + Service + DTO + Action) for an entity in one command';

    public function handle(): int
    {
        $name = $this->argument('name');
        $force = $this->option('force');

        $this->info("Generating complete module for: {$name}");
        $this->newLine();

        $this->call('make:repository', ['name' => $name, '--force' => $force]);
        $this->newLine();

        $this->call('make:service', ['name' => $name, '--repository' => $name, '--force' => $force]);
        $this->newLine();

        $this->call('make:dto', ['name' => $name, '--force' => $force]);
        $this->newLine();

        $normalizedName = str_replace('\\', '/', $name);
        $parts = explode('/', $normalizedName);
        $entityName = array_pop($parts);
        $subPath = count($parts) > 0 ? implode('/', $parts) . '/' : '';

        $this->call('make:action', [
            'name' => "{$subPath}Create{$entityName}Action",
            '--service' => "{$subPath}{$entityName}Service",
            '--dto' => "{$subPath}{$entityName}DTO",
            '--force' => $force,
        ]);

        $this->newLine();
        $this->info("Done! Check app/Repositories, app/Services, app/DTOs, and app/Actions directories.");
        $this->comment("Don't forget to bind the Repository Interface to the Repository implementation in AppServiceProvider or RepositoryServiceProvider.");

        return self::SUCCESS;
    }
}
