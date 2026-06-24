<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LaraBucket Server Configuration
    |--------------------------------------------------------------------------
    |
    | Here you can configure the storage server settings. This is only active
    | if 'enabled' is set to true.
    |
    */

    'server' => [
        // Enable LaraBucket server API endpoints
        'enabled' => env('LARABUCKET_SERVER_ENABLED', true),

        // Under-the-hood Laravel filesystem disk used to store the files
        'disk' => env('LARABUCKET_SERVER_DISK', 'local'),

        // Base prefix for the server API routes
        'route_prefix' => env('LARABUCKET_SERVER_ROUTE_PREFIX', 'api'),

        // Middleware applied to server API routes
        'middleware' => ['api'],

        // Super Admin credentials for the management dashboard
        'admin_email' => env('LARABUCKET_ADMIN_EMAIL', 'super@admin.com'),
        'admin_password' => env('LARABUCKET_ADMIN_PASSWORD', 'password'),

        // Public URL of the storage server (used to generate file URLs)
        'url' => env('LARABUCKET_SERVER_URL', env('APP_URL', 'http://localhost')),
    ],

];
