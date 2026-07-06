<?php

return [

    /*
    |--------------------------------------------------------------------------
    | المسارات والـ Namespaces
    |--------------------------------------------------------------------------
    | تقدر تغيّر أي مسار / namespace حسب هيكلة مشروعك.
    | */

    'repository' => [
        'namespace' => 'App\\Repositories',
        'path' => app_path('Repositories'),
        'interface_namespace' => 'App\\Repositories\\Contracts',
        'interface_path' => app_path('Repositories/Contracts'),
        'interface_suffix' => 'RepositoryInterface',
        // لو حاب يوصل تلقائي بالـ AppServiceProvider أثناء التوليد
        'auto_bind' => true,
    ],

    'service' => [
        'namespace' => 'App\\Services',
        'path' => app_path('Services'),
    ],

    'dto' => [
        'namespace' => 'App\\DTOs',
        'path' => app_path('DTOs'),
    ],

    'action' => [
        'namespace' => 'App\\Actions',
        'path' => app_path('Actions'),
    ],

    'model' => [
        'namespace' => 'App\\Models',
    ],

];
