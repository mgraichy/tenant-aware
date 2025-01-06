<?php

namespace Mgraichy\TenantAware;

//use Mgraichy\TenantAware\Models\TenantSwitcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Foundation\Application;

class TenantAware
{
    public function __construct(public Application $app) {}

    public function __invoke()
    {
        if (!($this->app->runningInConsole())) {
            $host = $this->app['request']->getHost();
            $tenantSwitcher = DB::connection('mysql')
                ->table('tenant_switcher')
                ->where('tenant_domain', $host)
                ->first();
            if (isset($tenantSwitcher->tenant_domain))
            {
                $this->switchTenant($tenantSwitcher);
                $this->configureQueue();
            }
        }
    }

    protected function switchTenant($tenantSwitcher)
    {
        config([
            'database.connections.tenant'          => config('tenant-aware.tenant'),
            'database.connections.tenant.database' => $tenantSwitcher->tenant_database,
            'cache.prefix' => "{$tenantSwitcher->tenant_database}_{$tenantSwitcher->id}",
        ]);

        // Remove dynamic connection from DatabaseManager in the service container, app('db').
        // Laravel automagically reconnects based on the config above, the next time it
        // tries to get a connection:
        DB::purge('tenant');
        app('cache')->purge(config('cache.default'));
    }

    protected function configureQueue()
    {
        //
    }
}