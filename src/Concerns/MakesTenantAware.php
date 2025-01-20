<?php

namespace Mgraichy\TenantAware\Concerns;

trait MakesTenantAware
{
    protected function configureDatabases(?\stdClass $tenantSwitcher = null): void
    {
        if (!$tenantSwitcher) {
            return;
        }

        // Configure the tenant switcher.
        // Have to include this condition since adding testing:
        // see tests/TestCase.php::defineEnvironment()
        if (!app()->environment('testing')) {
            config(['database.connections.tenant' => config('tenant-aware.tenant')]);
        }
        config(['database.connections.tenant.database' => $tenantSwitcher->tenant_database]);
        app('db')->purge('tenant');

        // Configure the cache DB:
        config(['cache.prefix' => "{$tenantSwitcher->tenant_database}_{$tenantSwitcher->id}"]);
        app('cache')->purge(config('cache.default'));
    }
}