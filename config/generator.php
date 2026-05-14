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
