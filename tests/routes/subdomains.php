<?php

use Illuminate\Support\Facades\Route;
use Mgraichy\TenantAware\Http\Middleware\TenantSessions;

$routes = function () {
    // The $tenant subdomain will be sent to any Controllers used instead of closures:
    Route::get('/', function(string $tenant) {
        // remove the conditions (running in console, actual host) for test:
        $host = app('request')->getHost();
        $tenantAware = app(\Mgraichy\TenantAware\TenantAware::class);
        $tenantAware->configureSubdomain($host);
        $tenantAware->configureQueue();

        $tenantSwitcher = app()['tenantSwitcher'];
        $currentDatabase = config('database.connections.tenant.database');
        $cachePrefix = config('cache.prefix');

        return response()->json([
            '$tenantVariableFromRoute::domain()' => $tenant,
            'tenantSwitcher' => $tenantSwitcher,
            'currentDatabase' => $currentDatabase,
            'cachePrefix' => $cachePrefix,
        ]);
    });
};

$domain = config('tenant-aware.domain');
Route::domain("{tenant}.$domain")->middleware(['web', TenantSessions::class])->group($routes);
