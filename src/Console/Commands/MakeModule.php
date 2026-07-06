<?php

namespace QusaiHomadi\LaravelArchitect\Console\Commands;

use Illuminate\Console\Command;

class MakeModule extends Command
{
    protected $signature = 'make:module {name : اسم الكيان, مثال: User}
                            {--force : استبدال الملفات لو موجودة}';

    protected $description = 'يولد دفعة وحدة كاملة: Repository + Service + DTO + Action لكيان واحد بأمر وحد (بديل لاستخدام --all)';

    public function handle(): int
    {
        $name = $this->argument('name');
        $force = $this->option('force');

        $this->info("جاري إنشاء الوحدة الكاملة لـ: {$name}");
        $this->newLine();

        $this->call('make:repository', ['name' => $name, '--force' => $force]);
        $this->newLine();

        $this->call('make:service', ['name' => $name, '--repository' => $name, '--force' => $force]);
        $this->newLine();

        $this->call('make:dto', ['name' => $name, '--force' => $force]);
        $this->newLine();

        $this->call('make:action', [
            'name' => "Create{$name}Action",
            '--service' => "{$name}Service",
            '--dto' => "{$name}DTO",
            '--force' => $force,
        ]);

        $this->newLine();
        $this->info("تم! تحقق من مجلدات app/Repositories, app/Services, app/DTOs, app/Actions");
        $this->comment("لا تنسَ ربط الـ Repository Interface بالـ Repository في AppServiceProvider أو RepositoryServiceProvider.");

        return self::SUCCESS;
    }
}
