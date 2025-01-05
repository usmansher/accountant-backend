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

    /**
     * This method is called by the Spatie package before saving the activity.
     */
    public function tapActivity(Activity $activity, string $eventName)
    {
        // If a user is logged in, associate them as the causer
        if (request()->user()) {
            $activity->causer_type = User::class;
            $activity->causer_id   = request()->user()->id;
        }

    }


    protected $fillable = [
        'name',
        'guard_name',
        'description',
    ];
}
