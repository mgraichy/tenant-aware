<?php

namespace Mgraichy\TenantAware;

use Illuminate\Support\ServiceProvider;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Log;
use Mgraichy\TenantAware\Models\TenantSwitcher;
use Mgraichy\TenantAware\Console\Commands\TenantAwareArtisan;

class TenantAwareServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantAware::class, function () {
            return new TenantAware();
        });
        $this->registerAdditionalClasses();
    }

    public function boot(): void
    {
        $loadFromConsumingApplication = base_path('routes/subdomains.php');
        file_exists($loadFromConsumingApplication) ?
            $this->loadRoutesFrom($loadFromConsumingApplication) :
            $this->loadRoutesFrom(__DIR__.'/../routes/subdomains.php');

        $this->publishesMigrations([
            __DIR__ . '/../database/migrations/system-db' => database_path('migrations/system-db'),
        ], 'tenant-aware-migrations');

        $this->publishes([
            __DIR__.'/../routes/subdomains.php'   => base_path('routes/subdomains.php'),
            __DIR__.'/../config/tenant-aware.php' => config_path('tenant-aware.php'),
        ], 'tenant-aware-subdomains');

        if ($this->app->runningInConsole()) {
            $this->commands([
                TenantAwareArtisan::class,
            ]);
        }

        if (!(app()->runningInConsole()) && $host = $this->app['request']->getHost()) {
            $tenantSwitcher = TenantSwitcher::where('tenant_domain', $host)->first();
            if (!$tenantSwitcher) {
                return;
            }
            $tenantSwitcher->configureDatabases()->registerTenantSwitcherInContainer();

            $this->configureQueue();
            $this->runAdditionalClasses();
        }
    }

    protected function configureQueue()
    {

        // $tenantIdForPayload = function () {
        //     return ['tenant_id' => 1];
        // };
        // app('queue')->createPayloadUsing($tenantIdForPayload);

        // $payloadEvent = function (JobProcessing $event) {
        //     Log::info('Event listener ran..');
        //     if ($event->job->payload()['tenant_id']) {
        //         Log::info('ID', ['tenant_id' => $event->job->payload()['tenant_id']]);
        //     }
        // };
        // app('events')->listen(JobProcessing::class, $payloadEvent);


        $tenantIdForPayload = function () {
            $app = app();
            $tenantSwitcher = $app['tenantSwitcher'] ?? null;
            $payload = $tenantSwitcher ?
                ['tenant_id' => $tenantSwitcher->id] :
                [];

            return $payload;
        };

        app('queue')->createPayloadUsing($tenantIdForPayload);

        $payloadEvent = function (JobProcessing $event) {
            Log::info('EVENT LISTENER HAS FINALLY RUN!!!', ['gawd'=>'damn']);
            if ($event->job->payload()['tenant_id']) {
                TenantSwitcher::find($event->job->payload()['tenant_id'])
                    ->configureDatabases()
                    ->registerTenantSwitcherInContainer();
            }
        };

        app('events')->listen(JobProcessing::class, $payloadEvent);
    }

    protected function registerAdditionalClasses()
    {
        $classMatrix = config('tenant-aware.additional_classes');

        if (empty($classMatrix)) {
            return;
        }

        foreach ($classMatrix as $classArray) {
            $class = $classArray[0];
            $parameters = $classArray[1] ?? [];
            $this->app->bind($class, function () use ($class, $parameters) {
                return $parameters ? new $class(...$parameters) : new $class();
            });
        }
    }

    protected function runAdditionalClasses()
    {
        $classMatrix = config('tenant-aware.additional_classes');
        if (!empty($classMatrix)) {
            foreach ($classMatrix as $classArray) {
                $class = $classArray[0];
                $this->app[$class]();
            }
        }
    }
}
