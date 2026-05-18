<?php

return [
    'default_blueprint' => 'api',

    'paths' => [
        'api_controller' => app_path('Http/Controllers/Api'),
        'web_controller' => app_path('Http/Controllers'),
        'models' => app_path('Models'),
        'services' => app_path('Services'),
        'repositories' => app_path('Repositories'),
        'factories' => database_path('factories'),
        'api_tests' => base_path('tests/Feature/Api'),
        'web_tests' => base_path('tests/Feature/Web'),
        'api_routes' => base_path('routes/api.php'),
        'web_routes' => base_path('routes/web.php'),
    ],

    'namespaces' => [
        'api_controller' => 'App\\Http\\Controllers\\Api',
        'web_controller' => 'App\\Http\\Controllers',
        'models' => 'App\\Models',
        'services' => 'App\\Services',
        'repositories' => 'App\\Repositories',
        'factories' => 'Database\\Factories',
    ],

    'repository' => [
        'default' => 'simple',
    ],

    'relationships' => [
        /*
         * Current V1 behavior is intentionally conservative:
         * only belongsTo relationships are generated automatically.
         *
         * Future options planned:
         * - none
         * - belongs_to
         * - all
         *
         * Inverse relationships and pivot relationships should only be
         * generated after strict internal conventions are defined.
         */
        'default' => 'belongs_to',

        'conventions' => [
            'foreign_key' => 'singular_id',
            'pivot_table' => 'alphabetical_singular',
        ],
    ],
];
