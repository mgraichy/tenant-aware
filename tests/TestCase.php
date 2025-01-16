<?php

namespace Tests;

use Mgraichy\TenantAware\Concerns\MakesTenantAware;
use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use MakesTenantAware;

    protected function getPackageProviders($app)
    {
        return [
            \Mgraichy\TenantAware\TenantAwareServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        tap($app['config'], function (Repository $config) {
            // If you want to put this package through tests,
            // change the following variables to reflect your own setup:
            $domain   = 'dev.testbase';
            $database = 'db_tests';
            $username = 'mg';
            $password = 'pw';

            $config->set('database.default', 'mysql');
            $config->set('database.connections.mysql', [
                'driver' => 'mysql',
                'url' => env('DB_URL'),
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => $database,
                'username' => $username,
                'password' => $password,
                'unix_socket' => env('DB_SOCKET', ''),
                'charset' => env('DB_CHARSET', 'utf8mb4'),
                'collation' => env('DB_COLLATION', 'utf8mb4_unicode_ci'),
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => true,
                'engine' => null,
                'options' => extension_loaded('pdo_mysql') ? array_filter([
                    \PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
                ]) : [],
            ]);

            $config->set('tenant-aware.domain', $domain);
            $config->set('tenant-aware.tenant.username', $username);
            $config->set('tenant-aware.tenant.password', $password);
        });
    }
}
