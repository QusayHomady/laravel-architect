<?php

namespace QusaiHomadi\LaravelArchitect;

use Illuminate\Support\ServiceProvider;
use QusaiHomadi\LaravelArchitect\Console\Commands\MakeRepository;
use QusaiHomadi\LaravelArchitect\Console\Commands\MakeService;
use QusaiHomadi\LaravelArchitect\Console\Commands\MakeAction;
use QusaiHomadi\LaravelArchitect\Console\Commands\MakeDTO;
use QusaiHomadi\LaravelArchitect\Console\Commands\MakeModule;

class GeneratorsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeRepository::class,
                MakeService::class,
                MakeAction::class,
                MakeDTO::class,
                MakeModule::class,
            ]);

            // نشر ملفات الـ stubs عشان تقدر تعدلها حسب ستايل مشروعك
            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/vendor/laravel-architect'),
            ], 'laravel-architect-stubs');

            // نشر ملف الإعدادات عشان تتحكم بالمسارات والـ namespaces
            $this->publishes([
                __DIR__ . '/../config/laravel-architect.php' => config_path('laravel-architect.php'),
            ], 'laravel-architect-config');
        }
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-architect.php',
            'laravel-architect'
        );
    }
}
