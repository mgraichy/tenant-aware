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

        //             Run __invoke():
        $this->app[TenantAware::class]();
        $this->runAdditionalClasses();
    }

    protected function registerAdditionalClasses()
    {
        $classArray = config('tenant-aware.additional_classes');
        if (!empty($classArray)) {
            foreach ($classArray as $class) {
                $this->app->bind($class::class, function () use ($class) {
                    if (!empty($class[1])) {
                        return new $class[0](...$class[1]);
                    }

                    return new $class[0]();
                });
            }
        }
    }

    protected function runAdditionalClasses()
    {
        $classArray = config('tenant-aware.additional_classes');
        if (!empty($classArray)) {
            //
        }
    }
}
