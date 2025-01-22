<?php

namespace App\Models;

// use TenantAware\Models\TenantSwitcherModel;
use Illuminate\Database\Eloquent\Model;

class Storefront extends Model // TenantSwitcherModel
{
    protected $table = 'users';

    public function method()
    {
        dump(config('database.connections.tenant'));
        // $this->configureDatabases()->registerTenantSwitcherInContainer();
        // return app('tenantSwitcher');
    }
}
