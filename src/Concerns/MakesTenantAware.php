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

        // Configure the cache DB, usually Redis:
        config(['cache.prefix' => "{$tenantSwitcher->tenant_database}_{$tenantSwitcher->id}"]);
        app('cache')->purge(config('cache.default'));

        $this->registerTenantInContainer($tenantSwitcher);
    }

    protected function configureQueue(): void
    {
        $tenantIdForPayload = function () {
            $tenantSwitcher = app('tenantSwitcher');
            $payload = $tenantSwitcher ?
                ['tenant_id' => $tenantSwitcher->id] :
                [];

            return $payload;
        };

        app('queue')->createPayloadUsing($tenantIdForPayload);

        $currentTenant = function (JobProcessing $event) {
            if (isset($event->job->payload()['tenant_id'])) {
                $tenantSwitcher = app('db')->table('tenant_switcher')
                    ->where('id', $event->job->payload()['tenant_id'])
                    ->first();
                $this->configureDatabases($tenantSwitcher);
                $this->registerTenantInContainer($tenantSwitcher);
            }
        };

        app('events')->listen(JobProcessing::class, $currentTenant);
    }

    protected function registerTenantInContainer(?\stdClass $tenantSwitcher = null): void
    {
        app()->forgetInstance('tenantSwitcher');
        app()->instance('tenantSwitcher', $tenantSwitcher);
    }
}