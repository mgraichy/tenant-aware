<?php

namespace TenantAware;

use Illuminate\Queue\Events\JobProcessing;
use TenantAware\Models\TenantSwitcherModel;

class TenantAware
{
    public function configureSubdomain(string $host): void
    {
        $tenantSwitcher = TenantSwitcherModel::where('tenant_domain', $host)->first();

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
                TenantSwitcherModel::find($event->job->payload()['tenant_id'])
                    ->configureDatabases()
                    ->registerTenantSwitcherInContainer();
            }
        };
        app('events')->listen(JobProcessing::class, $eventPayload);
    }
}