<?php

namespace Mgraichy\TenantAware;

//use Mgraichy\TenantAware\Models\TenantSwitcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Queue\Events\JobProcessing;

class TenantAware
{
    protected \stdClass $tenantSwitcher;

    public function __construct(public Application $app)
    {
        config(['database.connections.tenant' => config('tenant-aware.tenant')]);
    }

    public function __invoke()
    {
        if (!($this->app->runningInConsole())) {
            $host = $this->app['request']->getHost();
            $this->tenantSwitcher = DB::table('tenant_switcher')
                ->where('tenant_domain', $host)
                ->first();
            if (isset($this->tenantSwitcher->tenant_domain))
            {
                $this->configureDatabase();
                $this->configureCache();
                $this->configureQueue();
                // $this->configureFileSystem();
                // $this->etc();
                $this->registerTenantInContainer();
            }
        }
    }

    protected function configureDatabase(): void
    {
        config(['database.connections.tenant.database' => $this->tenantSwitcher->tenant_database]);
        // Remove dynamic connection from DatabaseManager in the service container, app('db').
        // Laravel automagically reconnects based on the config above.
        // purge('tenant') == purging 'database.connections.tenant':
        DB::purge('tenant');
    }

    protected function configureCache(): void
    {
        config(['cache.prefix' => "{$this->tenantSwitcher->tenant_database}_{$this->tenantSwitcher->id}"]);
        $this->app['cache']->purge(config('cache.default'));
    }

    protected function configureQueue(): void
    {
        $tenantId = function () {
            return $this->app['tenantConfigs'] ?
                    ['tenant_id' => $this->tenantSwitcher->id] :
                    [];
        };

        $this->app['queue']->createPayloadUsing($tenantId);

        $payload = function ($event) {
            // "tenant_id" is truly unique, in the system_db:
            if (isset($event->job->payload()['tenant_id'])) {
                $this->tenantSwitcher = DB::table('tenant_switcher')
                    ->where('tenant_id', $event->job->payload()['tenant_id'])
                    ->first();
                $this->configureDatabase();
                $this->registerTenantInContainer();
            }
        };

        $this->app['events']->listen(JobProcessing::class, $payload);
    }

    protected function registerTenantInContainer(): void
    {
        // Removes whatever instance was present from the container (if any):
        $this->app->forgetInstance('tenantConfigs');
        // Places the current (memory) instance in the container:
        $this->app->instance('tenantConfigs', [
            'tenantSwitcher' => $this->tenantSwitcher,
            'cache.prefix config' => config('cache.prefix'),
        ]);
    }
}