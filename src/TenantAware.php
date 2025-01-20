<?php

namespace Mgraichy\TenantAware;

use Illuminate\Queue\Events\JobProcessing;
use Mgraichy\TenantAware\Models\TenantSwitcherBaseModel;

class TenantAware
{
    public function configureSubdomain(string $host): void
    {
        $tenantSwitcher = TenantSwitcherBaseModel::where('tenant_domain', $host)->first();

        if (!$tenantSwitcher) {
            config(['database.default' => 'mysql']);
            return;
        }

        $tenantSwitcher->configureDatabases()->registerTenantSwitcherInContainer();
    }

    public function configureQueue()
    {
        $tenantIdForPayload = function () {
            $app = app();
            $tenantSwitcher = $app['tenantSwitcher'] ?? null;
            $payload = $tenantSwitcher ?
                ['tenant_id' => $tenantSwitcher->id] :
                [];

            return $payload;
        };
        app('queue')->createPayloadUsing($tenantIdForPayload);

        $eventPayload = function (JobProcessing $event) {
            if ($event->job->payload()['tenant_id']) {
                TenantSwitcherBaseModel::find($event->job->payload()['tenant_id'])
                    ->configureDatabases()
                    ->registerTenantSwitcherInContainer();
            }
        };
        app('events')->listen(JobProcessing::class, $eventPayload);
    }
}