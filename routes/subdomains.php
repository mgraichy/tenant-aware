<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StorefrontController;

$routes = function () {
    Route::get('/', [StorefrontController::class, 'index']);
    // Route::get('/', function ($tenant) {
    //     $app = app();
    //     if (isset($app['currentTenant'])) {
    //         dump('currentTenant (in "#instances"):',app('currentTenant'));
    //     } else {
    //         dump('currentTenant NOT LOADED!');
    //     }
    //     dump('current domain:', config('tenant-aware.domain'));
    //     dump('current subdomain:', $tenant);
    //     dump('config/database.php:', $app['config']['database']);
    //     dump($app);
    // });

};

$domain = config('tenant-aware.domain');
Route::domain("{tenant}.$domain")->group($routes);
