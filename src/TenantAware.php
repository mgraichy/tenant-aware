<?php

namespace Mgraichy\TenantAware;

use Mgraichy\TenantAware\Models\TenantSwitcher;
class TenantAware
{
    // use Concerns\MakesTenantAware;

    public function __invoke()
    {
        if (!(app()->runningInConsole())) {
            $host = app('request')->getHost();
            TenantSwitcher::where('tenant_domain', $host)
                ->first()
                ->configureDatabases()
                ->configureQueue();
        }

        // if (!(app()->runningInConsole())) {
        //     $host = app('request')->getHost();
        //     $tenantSwitcher = app('db')->table('tenant_switcher')
        //         ->where('tenant_domain', $host)
        //         ->first();
        //     if (isset($tenantSwitcher->tenant_domain)) {
        //         $this->configureDatabases($tenantSwitcher);
        //         $this->configureQueue();
        //     }
        // }
    }
}