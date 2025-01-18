<?php

use Illuminate\Support\Facades\Route;
use Mgraichy\TenantAware\Http\Middleware\TenantSessions;

$routes = function () {
    Route::get('/', function(string $tenant) {
        echo "The \$tenant ('{$tenant}') subdomain will also be sent to any Controllers used instead of closures.";
    });
};

$domain = config('tenant-aware.domain');
Route::domain("{tenant}.$domain")->middleware(['web', TenantSessions::class])->group($routes);
