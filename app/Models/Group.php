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
}
