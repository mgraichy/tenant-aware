<?php

namespace Tests\App\Models;

use Illuminate\Database\Eloquent\Model;

class Storefront extends Model
{
    protected $connection = 'tenant';
    protected $table = 'users';
}
