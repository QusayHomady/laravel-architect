<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use QusaiHomadi\LaravelArchitect\Console\Commands\Concerns\ResolvesStubs;

class MakeDTO extends Command
{
    use ResolvesStubs;

    protected $signature = 'make:dto {name : The name of the DTO, e.g., User or UserDTO}
                            {--all : Generate the Repository + Service + Action for the entity as well}
                            {--force : Overwrite the files if they already exist}';

    protected $description = 'Create a typed DTO Class from an Eloquent model (add --all to generate the complete module)';

    protected array $typeMap = [
        'int' => 'int',
        'integer' => 'int',
        'real' => 'float',
        'float' => 'float',
        'double' => 'float',
        'decimal' => 'float',
        'string' => 'string',
        'bool' => 'bool',
        'boolean' => 'bool',
        'object' => 'object',
        'array' => 'array',
        'json' => 'array',
        'collection' => '\Illuminate\Support\Collection',
        'date' => '\Carbon\Carbon',
        'datetime' => '\Carbon\Carbon',
        'custom_datetime' => '\Carbon\Carbon',
        'immutable_date' => '\Carbon\CarbonImmutable',
        'immutable_datetime' => '\Carbon\CarbonImmutable',
        'timestamp' => 'int',
    ];

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

        // Try to resolve the model class to extract attributes and casts
        $modelClass = $this->resolveModelClass($cleanName);
        $modelExists = false;
        $fillable = [];
        $casts = [];

        if ($modelClass !== null && is_subclass_of($modelClass, Model::class)) {
            $modelExists = true;
            /** @var Model $model */
            $model = new $modelClass;
            $fillable = $model->getFillable();
            $casts = $model->getCasts();
        } else {
            $this->warn("Could not resolve an Eloquent model class for [{$cleanName}]. Generating a default empty DTO.");
        }

        $properties = $this->buildConstructorProperties($fillable, $casts, $modelExists);
        $methods = $this->buildMethods($dtoClass, $modelClass ?? "App\\Models\\{$entityName}", $fillable, $modelExists);
        $imports = $this->buildImports($fillable, $casts, $modelClass ?? "App\\Models\\{$entityName}", $modelExists);

        $content = $this->buildFromStub('dto', [
            'namespace' => $namespace,
            'class' => $dtoClass,
            'imports' => $imports,
            'properties' => $properties,
            'methods' => $methods,
            'modelClass' => $modelClass ?? "App\\Models\\{$entityName}",
            'modelName' => $cleanName,
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

    protected function resolveModelClass(string $input): ?string
    {
        $input = str_replace('/', '\\', $input);
        $input = ltrim($input, '\\');

        $candidates = [
            $input,
            'App\\Models\\' . $input,
            'App\\' . $input,
        ];

        foreach ($candidates as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function resolvePhpType(string $field, array $casts): string
    {
        if (!array_key_exists($field, $casts)) {
            return 'mixed';
        }

        $cast = strtolower($casts[$field]);
        $cast = explode(':', $cast)[0];

        return $this->typeMap[$cast] ?? 'mixed';
    }

    protected function buildConstructorProperties(array $fillable, array $casts, bool $modelExists): string
    {
        if (!$modelExists) {
            return "        // Define DTO properties here based on the data you need\n        // Example: public readonly string \$name, public readonly string \$email";
        }

        $readonly = config('laravel-architect.dto.readonly', true) ? 'readonly ' : '';
        $lines = [];

        foreach ($fillable as $field) {
            $type = $this->resolvePhpType($field, $casts);
            $property = Str::camel($field);
            $nullablePrefix = $type === 'mixed' ? '' : '?';

            $lines[] = "        public {$readonly}{$nullablePrefix}{$type} \${$property} = null,";
        }

        return implode("\n", $lines) ?: '        //';
    }

    protected function buildImports(array $fillable, array $casts, string $modelClass, bool $modelExists): string
    {
        $used = [];

        if ($modelExists) {
            foreach ($fillable as $field) {
                $type = $this->resolvePhpType($field, $casts);
                if (str_starts_with($type, '\\')) {
                    $used[$type] = true;
                }
            }

            if (config('laravel-architect.dto.generate_from_model', true)) {
                $used['\\' . ltrim($modelClass, '\\')] = true;
            }
        }

        if (config('laravel-architect.dto.generate_from_request', true)) {
            $used['\Illuminate\Http\Request'] = true;
        }

        if (empty($used)) {
            return '';
        }

        $lines = array_map(fn ($fqcn) => 'use ' . ltrim($fqcn, '\\') . ';', array_keys($used));
        sort($lines);

        return implode("\n", $lines) . "\n";
    }

    protected function buildMethods(string $dtoClassName, string $modelClass, array $fillable, bool $modelExists): string
    {
        if (!$modelExists) {
            return <<<PHP
    public static function fromArray(array \$data): self
    {
        return new self(
            // ...\$data['name'], \$data['email']
        );
    }

    public function toArray(): array
    {
        return get_object_vars(\$this);
    }
PHP;
        }

        $methods = [];

        if (config('laravel-architect.dto.generate_from_model', true)) {
            $methods[] = $this->fromModelMethod($dtoClassName, $modelClass, $fillable);
        }

        if (config('laravel-architect.dto.generate_from_array', true)) {
            $methods[] = $this->fromArrayMethod($dtoClassName, $fillable);
        }

        if (config('laravel-architect.dto.generate_from_request', true)) {
            $methods[] = $this->fromRequestMethod($dtoClassName, $fillable);
        }

        if (config('laravel-architect.dto.generate_to_array', true)) {
            $methods[] = $this->toArrayMethod($fillable);
        }

        return implode("\n\n", $methods);
    }

    protected function fromModelMethod(string $dtoClassName, string $modelClass, array $fillable): string
    {
        $args = [];
        foreach ($fillable as $field) {
            $property = Str::camel($field);
            $args[] = "            {$property}: \$model->{$field},";
        }
        $argsBlock = implode("\n", $args) ?: '            //';

        return <<<PHP
    public static function fromModel({$this->shortName($modelClass)} \$model): self
    {
        return new self(
            {$argsBlock}
        );
    }
PHP;
    }

    protected function fromArrayMethod(string $dtoClassName, array $fillable): string
    {
        $args = [];
        foreach ($fillable as $field) {
            $property = Str::camel($field);
            $args[] = "            {$property}: \$data['{$field}'] ?? null,";
        }
        $argsBlock = implode("\n", $args) ?: '            //';

        return <<<PHP
    public static function fromArray(array \$data): self
    {
        return new self(
            {$argsBlock}
        );
    }
PHP;
    }

    protected function fromRequestMethod(string $dtoClassName, array $fillable): string
    {
        $fields = implode(', ', array_map(fn ($f) => "'{$f}'", $fillable));

        return <<<PHP
    public static function fromRequest(Request \$request): self
    {
        return self::fromArray(\$request->only([{$fields}]));
    }
PHP;
    }

    protected function toArrayMethod(array $fillable): string
    {
        $lines = [];
        foreach ($fillable as $field) {
            $property = Str::camel($field);
            $lines[] = "            '{$field}' => \$this->{$property},";
        }
        $block = implode("\n", $lines) ?: '            //';

        return <<<PHP
    public function toArray(): array
    {
        return [
            {$block}
        ];
    }
PHP;
    }

    protected function shortName(string $fqcn): string
    {
        return class_basename($fqcn);
    }
}
