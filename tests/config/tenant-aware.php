<?php

return [

    'domain' => '',

    'additional_classes' => [
        [
            'FQCN' => \Tests\App\TenantAware\ClassOne::class
        ],
        [
            'FQCN' => \Tests\App\TenantAware\ClassTwo::class,
            '__construct-params' => [
                [1,2,3],
                'hey',
                \Tests\App\Models\User::class,
            ],
            '__invoke-params' => [
                \Tests\App\TenantAware\ClassOne::class,
            ],
        ],
    ],

    'tenant' => [
        'driver' => 'mysql',
        'url' => env('DB_URL'),
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', '3306'),
        'database' => null,
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'unix_socket' => env('DB_SOCKET', ''),
        'charset' => env('DB_CHARSET', 'utf8mb4'),
        'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
    ],
];