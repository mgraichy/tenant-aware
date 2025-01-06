<?php

namespace Mgraichy\TenantAware\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantAwareModel extends Model
{
    use HasFactory;
    protected $connection = 'tenant';
}
