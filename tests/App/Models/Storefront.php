<?php

namespace App\Models;

// use Mgraichy\TenantAware\Models\TenantSwitcherBaseModel;
use Illuminate\Database\Eloquent\Model;

class Storefront extends Model//TenantSwitcherBaseModel
{
    protected $table = 'users';


    public function method()
    {
        dump(config('database.connections.tenant'));
        // $this->configureDatabases()->registerTenantSwitcherInContainer();
        // return app('tenantSwitcher');
    }
}
