<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Permission extends SpatiePermission
{
    use HasFactory, CentralConnection;

    protected $primaryKey = 'id';
    protected $table = 'permissions';
    protected $guard = 'api';
    protected $guard_name = 'api';

    protected $fillable = ['name', 'guard_name', 'description'];
}
