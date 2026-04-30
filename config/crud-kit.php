<?php

return [
    'paths' => [
        'controllers' => app_path('Http/Controllers/Api'),
        'requests' => app_path('Http/Requests'),
        'resources' => app_path('Http/Resources'),
        'models' => app_path('Models'),
        'services' => app_path('Services'),
        'repositories' => app_path('Repositories'),
    ],

    'namespaces' => [
        'controllers' => 'App\\Http\\Controllers\\Api',
        'requests' => 'App\\Http\\Requests',
        'resources' => 'App\\Http\\Resources',
        'services' => 'App\\Services',
        'repositories' => 'App\\Repositories',
        'models' => 'App\\Models',
    ],
];
