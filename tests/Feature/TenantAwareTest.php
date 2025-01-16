<?php

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

it('publishes all files for TenantAware', function() {
    $command = 'vendor:publish --tag=tenant-aware-migrations --tag=tenant-aware-subdomains';
    $this->artisan($command)->assertSuccessful();
});

it('migrates the main database', function() {
    $this->artisan('migrate')->assertSuccessful();
});

it('installs the tenant_switcher table', function() {
    $command = 'migrate --path=database/migrations/system-db --realpath';
    $this->artisan($command)->assertSuccessful();
});

it('inserts to or updates the tenant_switcher table', function() {
    $domain = config('tenant-aware.domain');
    $result = DB::table('tenant_switcher')->upsert(
        [
            [
                'tenant_name' => 'Films Ltd.',
                'tenant_domain' => "films.$domain",
                'tenant_database' => 'db_films',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
            [
                'tenant_name' => 'Elephants Inc.',
                'tenant_domain' => "elephants.$domain",
                'tenant_database' => 'db_elephants',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
        ],
        ['tenant_domain'],
        ['tenant_name','tenant_domain','tenant_database','created_at','updated_at']
    );
    expect($result)->toBeTruthy();
});

it('installs database tables corresponding to subdomains', function() {
    $command = 'tenants:foreach migrate --params="--path=database/migrations/testing-tenants --realpath"';
    $this->artisan($command)->assertSuccessful();
});


it('inserts to or updates the users table in subdomain databases', function() {
    $tenantSwitcher = DB::table('tenant_switcher')->get();
    foreach ($tenantSwitcher as $ts) {
        // From the MakesTenantAware trait in src/TenantAwareServiceProvider.php:
        $this->configureDatabases($ts);
        $result = DB::connection('tenant')->table('users')->upsert(
            [
                [
                    'name' => 'Generic Name',
                    'email' => 'genericname@example.com',
                    'email_verified_at' => null,
                    'password' => '$2y$12$cM0ZsBZCubGPXVTOZYuJ3ue060gSKm42Cu8XYE.K1LzmSwpdztOEi',
                    'remember_token' => '',
                    'created_at' => CarbonImmutable::now(),
                    'updated_at' => CarbonImmutable::now(),
                ],
            ],
            ['email'],
            ['name','email','email_verified_at','password','remember_token','created_at','updated_at']
        );
        expect($result)->toBeTruthy();
    }
});
