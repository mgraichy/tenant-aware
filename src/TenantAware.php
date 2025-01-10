<?php

namespace Mgraichy\TenantAware;

class TenantAware
{
    use Concerns\MakesTenantAware;

    public function __invoke()
    {
        if (!(app()->runningInConsole())) {
            $host = app('request')->getHost();
            $tenantSwitcher = app('db')->table('tenant_switcher')
                ->where('tenant_domain', $host)
                ->first();
            if (isset($tenantSwitcher->tenant_domain)) {
                $this->configureDatabases($tenantSwitcher);
                $this->configureQueue();
            }
        }
    }
}