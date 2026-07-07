<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Namespaces and Paths
    |--------------------------------------------------------------------------
    | You can customize namespaces and folder paths according to your project structure.
    | */

    'repository' => [
        'namespace' => 'App\\Repositories',
        'path' => app_path('Repositories'),
        'interface_namespace' => 'App\\Repositories\\Contracts',
        'interface_path' => app_path('Repositories/Contracts'),
        'interface_suffix' => 'RepositoryInterface',
        // Enable/disable binding suggestions during generation
        'auto_bind' => true,
    ],

    'service' => [
        'namespace' => 'App\\Services',
        'path' => app_path('Services'),
    ],

    'dto' => [
        'namespace' => 'App\\DTOs',
        'path' => app_path('DTOs'),
        'readonly' => true,
        'generate_from_model' => true,
        'generate_from_array' => true,
        'generate_from_request' => true,
        'generate_to_array' => true,
    ],

    'action' => [
        'namespace' => 'App\\Actions',
        'path' => app_path('Actions'),
    ],

    'model' => [
        'namespace' => 'App\\Models',
    ],

];
