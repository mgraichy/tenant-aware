<?php

use Carbon\CarbonImmutable;

it('publishes all files for TenantAware', function() {
    $command = 'vendor:publish --tag=tenant-aware-migrations --tag=tenant-aware-subdomains';
    $this->artisan($command)->assertSuccessful();
});

it('installs the tenant_switcher table in the main database', function() {
    $databasePath = database_path('migrations/system-db');
    $this->artisan("migrate --path=$databasePath --realpath")->assertSuccessful();
});

it('INSERTs or UPDATEs the tenant_switcher table', function() {
    $domain = config('tenant-aware.domain');
    $result = app('db')->table('tenant_switcher')->upsert(
        [
            [
                'tenant_name' => 'ElePHPants Inc.',
                'tenant_domain' => "elephpant.$domain",
                'tenant_database' => 'db_elephpants',
                'created_at' => CarbonImmutable::now(),
                'updated_at' => CarbonImmutable::now(),
            ],
        ],
        ['tenant_domain'],
        ['tenant_name','tenant_domain','tenant_database','created_at','updated_at']
    );
    expect($result)->toBeTruthy();
});


it('installs the users table on subdomains', function() {
    $databasePath = database_path('migrations/tenants');
    $command = "tenants:foreach migrate --params='--database=tenant --path={$databasePath} --realpath'";
    $this->artisan($command)->assertSuccessful();
});

it('INSERTs or UPDATEs the users table in subdomain databases', function() {
    $tenantSwitcher = app('db')->table('tenant_switcher')->get();
    foreach ($tenantSwitcher as $ts) {
        // From the MakesTenantAware trait in src/TenantAwareServiceProvider.php:
        $this->configureDatabases($ts);
        $result = app('db')->connection('tenant')->table('users')->upsert(
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

it('tests a subdomain\'s container', function() {
    $domain = config('tenant-aware.domain');
    $response = $this->get("https://elephpant.$domain");
    $response->assertStatus(200);
    $json = $response->json();
    $type = gettype($json);
    expect($type)->toBe('array')
        ->and($json['$tenantVariableFromRoute::domain()'])->toBe('elephpant')
        ->and($json['currentDatabase'])->toBe('db_elephpants')
        ->and($json['cachePrefix'])->toBe('db_elephpants_1')
        ->and($json['tenantSwitcher']['tenant_name'])->toBe('ElePHPants Inc.')
        ->and($json['tenantSwitcher']['tenant_domain'])->toBe('elephpant.example.com')
        ->and($json['tenantSwitcher']['tenant_database'])->toBe('db_elephpants');
});


