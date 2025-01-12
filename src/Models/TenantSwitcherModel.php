<?php

namespace Mgraichy\TenantAware\Models;

use Illuminate\Database\Eloquent\Model;

class TenantSwitcherModel extends Model
{
    protected $table = 'tenant_switcher';

    public function configureDatabases(): TenantSwitcherModel
    {
        // Switch to the appropriate tenant in the configs:
        config(['database.connections.tenant' => config('tenant-aware.tenant')]);
        config(['database.connections.tenant.database' => $this->tenant_database]);
        app('db')->purge('tenant');

        // Configure the cache DB, usually Redis:
        config(['cache.prefix' => "{$this->tenant_database}_{$this->id}"]);
        app('cache')->purge(config('cache.default'));

        return $this;
    }

    public function registerTenantSwitcherInContainer(): TenantSwitcherModel
    {
        app()->forgetInstance('tenantSwitcher');
        app()->instance('tenantSwitcher', $this);

        return $this;
    }
}