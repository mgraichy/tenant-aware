<?php

namespace Mgraichy\TenantAware\Concerns;

use Illuminate\Queue\Events\JobProcessing;

trait MakesTenantAware
{
    protected function configureDatabases(?\stdClass $tenantSwitcher = null): void
    {
        if (!$tenantSwitcher) {
            return;
        }

        // Configure the tenant switcher:
        config(['database.connections.tenant' => config('tenant-aware.tenant')]);
        config(['database.connections.tenant.database' => $tenantSwitcher->tenant_database]);
        app('db')->purge('tenant');

        // Configure the cache DB:
        config(['cache.prefix' => "{$tenantSwitcher->tenant_database}_{$tenantSwitcher->id}"]);
        app('cache')->purge(config('cache.default'));
    }
}