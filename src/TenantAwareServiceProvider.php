<?php

namespace Mgraichy\TenantAware;

use Illuminate\Support\ServiceProvider;
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
        $consumingApplication = base_path('routes/subdomains.php');
        file_exists($consumingApplication) ?
            $this->loadRoutesFrom($consumingApplication) :
            $this->loadRoutesFrom(__DIR__.'/../routes/subdomains.php');

        if (!is_dir(database_path('migrations/system-db'))) {
            $this->publishesMigrations([
                __DIR__ . '/../database/migrations/system-db' => database_path('migrations/system-db'),
                __DIR__ . '/../database/migrations/tenants' => database_path('migrations/tenants'),
            ], 'tenant-aware-migrations');
        }

        $this->publishes([
            __DIR__.'/../routes/subdomains.php'   => base_path('routes/subdomains.php'),
            __DIR__.'/../config/tenant-aware.php' => config_path('tenant-aware.php'),
        ], 'tenant-aware-subdomains');

        if ($this->app->runningInConsole()) {
            $this->commands([
                TenantAwareArtisan::class,
            ]);
        }

        $tenantAware = $this->app[TenantAware::class];
        if (!(app()->runningInConsole()) && $host = $this->app['request']->getHost()) {
            $tenantAware($host);
        }
        // For e.g., tests, queues, "php artisan ...", etc.
        // all of which run within the console:
        $tenantAware->configureQueue();
        $this->runAdditionalClasses();
    }

    protected function registerAdditionalClasses(): void
    {
        $classMatrix = config('tenant-aware.additional_classes');

        if (empty($classMatrix)) {
            return;
        }

        foreach ($classMatrix as $classArray) {
            $class = $classArray['FQCN'];
            $constructParams = $classArray['__construct-params'] ?? [];
            $this->app->bind($class, function () use ($class, $constructParams) {
                return $constructParams ? new $class(...$constructParams) : new $class();
            });
        }
    }

    protected function runAdditionalClasses(): void
    {
        $classMatrix = config('tenant-aware.additional_classes');
        if (empty($classMatrix)) {
            return;
        }

        foreach ($classMatrix as $classArray) {
            $class = $classArray['FQCN'];
            $invokeParams = $classArray['__invoke-params'] ?? [];
            $invokeParams ? $this->app[$class](...$invokeParams) : $this->app[$class]();
        }
    }
}
