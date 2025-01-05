<?php
// app/Models/Group.php

namespace App\Models;

use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;

class Group extends Model
{
    use ActivityTrait;
    protected $table = 'groups';
    protected $keyType = 'string';
    public $incrementing = false; // Because we're using UUIDs

    protected $fillable = [
        'id',
        'parent_id',
        'name',
        'code',
        'affects_gross',
    ];

    protected static function boot()
    {
        parent::boot();

        // Automatically generate UUIDs for new records
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Group::class, 'parent_id');
    }

    public function ledgers(): HasMany
    {
        return $this->hasMany(Ledger::class, 'group_id');
    }



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
}
