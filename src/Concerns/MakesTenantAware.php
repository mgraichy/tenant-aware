<?php

namespace Mgraichy\TenantAware\Concerns;

use Illuminate\Queue\Events\JobProcessing;
trait MakesTenantAware
{
    protected function configureDatabase(?\stdClass $tenantSwitcher = null): void
    {
        if (!$tenantSwitcher) {
            return;
        }

        config(['database.connections.tenant' => config('tenant-aware.tenant')]);
        config(['database.connections.tenant.database' => $tenantSwitcher->tenant_database]);
        app('db')->purge('tenant');
    }

    protected function configureCache(?\stdClass $tenantSwitcher = null): void
    {
        if (!$tenantSwitcher) {
            return;
        }

        config(['cache.prefix' => "{$tenantSwitcher->tenant_database}_{$tenantSwitcher->id}"]);
        app('cache')->purge(config('cache.default'));
    }

    protected function configureQueue(): void
    {
        // Add a tenant ID to the payload iff app('tenantConfigs') is present
        // (meaning this is in fact a request coming from a subdomain).
        // NB: you're adding the ID to the payload, and not to "app('tenantConfigs')":
        $tenantIdForPayload = function () {
            $tenantConfigs = app('tenantConfigs');
            return $tenantConfigs ?
                ['tenant_id' => $tenantConfigs['tenantSwitcher']->id] :
                [];
        };

        app('queue')->createPayloadUsing($tenantIdForPayload);

        $currentTenant = function ($event) {
            if (isset($event->job->payload()['tenant_id'])) {
                $tenantSwitcher = app('db')->table('tenant_switcher')
                    // If a tenant_id has been set on this payload, use it in the system_db
                    // to get which tenant (subdomain) this is:
                    ->where('id', $event->job->payload()['tenant_id'])
                    ->first();
                $this->configureDatabase($tenantSwitcher);
                $this->registerTenantInContainer($tenantSwitcher);
            }
        };

        app('events')->listen(JobProcessing::class, $currentTenant);
    }

    protected function registerTenantInContainer(?\stdClass $tenantSwitcher = null): void
    {
        app()->forgetInstance('tenantConfigs');
        app()->instance('tenantConfigs', [
            'tenantSwitcher' => $tenantSwitcher,
            'cache.prefix config' => config('cache.prefix'),
        ]);
    }
}