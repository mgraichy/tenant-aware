<?php

namespace Mgraichy\TenantAware;

use Illuminate\Support\ServiceProvider;

class TenantAwareServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantAware::class, function () {
            return new TenantAware($this->app);
        });
        $this->registerAdditionalClasses();
    }

    public function boot(): void
    {
        if (file_exists(base_path('routes/subdomains.php'))) {
            $this->loadRoutesFrom(base_path('routes/subdomains.php'));
        } else {
            $this->loadRoutesFrom(__DIR__.'/../routes/subdomains.php');
        }

        $this->publishesMigrations([
            __DIR__ . '/../database/migrations/system-db' => database_path('migrations/system-db'),
        ], 'tenant-aware-migrations');

        $this->publishes([
            __DIR__.'/../routes/subdomains.php'   => base_path('routes/subdomains.php'),
            __DIR__.'/../config/tenant-aware.php' => config_path('tenant-aware.php'),
        ], 'tenant-aware-subdomains');

        if ($this->app['request']->getHost()) {
            $this->app[TenantAware::class]();
            $this->runAdditionalClasses();
        }
    }

    protected function registerAdditionalClasses()
    {
        $classMatrix = config('tenant-aware.additional_classes');
        if (!empty($classMatrix)) {
            foreach ($classMatrix as $classArray) {
                $class = $classArray[0];
                $parameters = $classArray[1] ?? [];
                $this->app->bind($class, function () use ($class, $parameters) {
                    return $parameters ? new $class(...$parameters) : new $class();
                });
            }
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
