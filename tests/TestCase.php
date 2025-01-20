<?php

namespace Tests;

use Mgraichy\TenantAware\Concerns\MakesTenantAware;
use Illuminate\Contracts\Config\Repository;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use MakesTenantAware;

    // If you'd like to run tests on this package,
    // change at least $username and $password to reflect your own setup:
    protected $username = 'mg';
    protected $password = 'pw';
    protected $domain   = 'example.com';
    protected $database = 'db_tests';

    protected function getPackageProviders($app)
    {
        return [
            \Mgraichy\TenantAware\TenantAwareServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app)
    {
        config(['database.default' => 'mysql']);
        config(['database.connections.mysql.username' => $this->username]);
        config(['database.connections.mysql.password' => $this->password]);

        $this->createDbs();

        tap($app['config'], function (Repository $config) {
            $config->set('database.connections.mysql.database', $this->database);
            $config->set('tenant-aware.domain', $this->domain);
            $config->set('database.connections.tenant', config('tenant-aware.tenant'));
            $config->set('database.connections.tenant.username', $this->username);
            $config->set('database.connections.tenant.password', $this->password);
        });

        $app['db']->purge();
    }

    protected function createDbs(): void
    {
        $db = app('db')->connection('mysql');

        $exists = <<<SQL
            SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?;
        SQL;

        if (empty($db->select($exists, ['db_tests']))) {
            $dbTests = <<<SQL
                CREATE SCHEMA IF NOT EXISTS `db_tests`
                    DEFAULT CHARACTER SET = utf8mb4
                    COLLATE = utf8mb4_unicode_ci;
            SQL;

            $db->select($dbTests);

            $dbElephpants = <<<SQL
                CREATE SCHEMA IF NOT EXISTS `db_elephpants`
                    DEFAULT CHARACTER SET = utf8mb4
                    COLLATE = utf8mb4_unicode_ci;
            SQL;

            $db->select($dbElephpants);
        }
    }
}
