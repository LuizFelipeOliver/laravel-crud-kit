<?php

return [
    'default_blueprint' => 'api',

    'paths' => [
        'api_controller' => app_path('Http/Controllers/Api'),
        'web_controller' => app_path('Http/Controllers'),
        'models' => app_path('Models'),
        'services' => app_path('Services'),
        'repositories' => app_path('Repositories'),
    ],

    'namespaces' => [
        'api_controller' => 'App\\Http\\Controllers\\Api',
        'web_controller' => 'App\\Http\\Controllers',
        'models' => 'App\\Models',
        'services' => 'App\\Services',
        'repositories' => 'App\\Repositories',
    ],

    'repository' => [
        'default' => 'simple',
    ],
];
