<?php

namespace Mgraichy\TenantAware;

use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobProcessing;


class TenantAware
{
    public function __invoke()
    {
        if (!(app()->runningInConsole())) {

            $host = app('request')->getHost();
            $tenantSwitcher = app('db')->table('tenant_switcher')
                ->where('tenant_domain', $host)
                ->first();
            if (isset($tenantSwitcher->tenant_domain)) {
                $this->configureDatabase($tenantSwitcher);
                $this->configureCache($tenantSwitcher);
                $this->configureQueue($tenantSwitcher);
                $this->registerTenantInContainer($tenantSwitcher);
            }
        }
    }

    protected function configureDatabase(?\stdClass $tenantSwitcher = null): void
    {
        if (!$tenantSwitcher) {
            return;
        }

        // config/database.php:
        config(['database.connections.tenant' => config('tenant-aware.tenant')]);
        config(['database.connections.tenant.database' => $tenantSwitcher->tenant_database]);
        // app('db')
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

    protected function configureQueue(?\stdClass $tenantSwitcher = null): void
    {
        if (!$tenantSwitcher) {
            return;
        }

        $tenantId = function () use ($tenantSwitcher) {
            return app('tenantConfigs') ?
                    ['tenant_id' => $tenantSwitcher->id] :
                    [];
        };

        app('queue')->createPayloadUsing($tenantId);

        $payload = function ($event) {
            // "tenant_id" is truly unique, in the system_db:
            if (isset($event->job->payload()['tenant_id'])) {
                $tenantSwitcher = app('db')->table('tenant_switcher')
                    ->where('tenant_id', $event->job->payload()['tenant_id'])
                    ->first();
                $this->configureDatabase();
                $this->registerTenantInContainer($tenantSwitcher);
            }
        };

        app('events')->listen(JobProcessing::class, $payload);
    }

    protected function registerTenantInContainer(?\stdClass $tenantSwitcher = null): void
    {
        if (!$tenantSwitcher) {
            return;
        }

        // Removes whatever instance was present from the container (if any):
        app()->forgetInstance('tenantConfigs');
        // Places the current (memory) instance in the container:
        app()->instance('tenantConfigs', [
            'tenantSwitcher' => $tenantSwitcher,
            'cache.prefix config' => config('cache.prefix'),
        ]);
    }
}