<?php

use Illuminate\Support\Facades\Route;
use Mgraichy\TenantAware\Http\Middleware\TenantSessions;

$routes = function () {
    Route::get('/', function($tenant) {
        echo 'Please fill in your own routes here :)';
    });
};

$domain = config('tenant-aware.domain');
Route::domain("{tenant}.$domain")->middleware(['web', TenantSessions::class])->group($routes);
