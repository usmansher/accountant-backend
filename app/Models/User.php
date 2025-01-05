<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Ramsey\Uuid\Uuid;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\Permission\Traits\HasRoles;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class User extends Authenticatable
{
    use CentralConnection, HasFactory, Notifiable, ActivityTrait, HasRoles, CausesActivity;
    // protected $keyType = 'string';
    // public $incrementing = false;
    protected $guard_name = 'api';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['role_name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }



    // protected static function boot()
    // {
    //     parent::boot();
    //     static::creating(function ($model) {
    //         $model->id = Uuid::uuid4()->toString();
    //     });
    // }


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

    public function getRoleNameAttribute()
    {
        return $this->roles->first()->name ?? 'No Role';
    }
}
