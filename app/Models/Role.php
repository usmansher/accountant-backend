<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Permission\Models\Role as ModelsRole;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Role extends ModelsRole
{
    use HasFactory, CentralConnection;
    // use LogsActivity;

    protected $primaryKey = 'id';
    protected $table = 'roles';
    protected $guard = 'api';
    protected $guard_name = 'api';

    protected function getDefaultGuardName(): string
    {
        return 'api';
    }


    // Specify the attributes you want to log
    protected static $logAttributes = ['name', 'description'];

    // Customize the log name
    protected static $logName = 'role';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }


    protected $fillable = [
        'name',
        'guard_name',
        'description',
    ];
}
