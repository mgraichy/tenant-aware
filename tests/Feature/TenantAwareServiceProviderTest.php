<?php

// use Carbon\CarbonImmutable;

it('loads the service provider', function () {
    $loadedProviders = app()->getLoadedProviders();
    $serviceProvider = \TenantAware\TenantAwareServiceProvider::class;

    expect($loadedProviders)->toHaveKey($serviceProvider)
        ->and($loadedProviders[$serviceProvider])->toBeTrue();
});

it('binds all services', function () {
    $abstract = \TenantAware\TenantAware::class;
    $concrete = \TenantAware\TenantAware::class;

    $resolvedInstance = app($abstract);

    expect($resolvedInstance)
        ->toBeInstanceOf($concrete);
});
