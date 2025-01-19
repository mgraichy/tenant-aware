<?php

// use Carbon\CarbonImmutable;

it('loads the service provider', function () {
    $loadedProviders = app()->getLoadedProviders();
    $serviceProvider = \Mgraichy\TenantAware\TenantAwareServiceProvider::class;

    expect($loadedProviders)->toHaveKey($serviceProvider)
        ->and($loadedProviders[$serviceProvider])->toBeTrue();
});

it('binds all services', function () {
    $abstract = \Mgraichy\TenantAware\TenantAware::class;
    $concrete = \Mgraichy\TenantAware\TenantAware::class;

    $resolvedInstance = app($abstract);

    expect($resolvedInstance)
        ->toBeInstanceOf($concrete);
});
