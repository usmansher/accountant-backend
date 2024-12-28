<?php

namespace App\Models;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class Base extends Model
{
    // use ActivityTrait;
    // use TableTrait;

    public $hasHash = false;
    public $hasNumber = false;
    public $hasReference = false;
    public $hasUser = false;
    public $incrementing = false;
    protected $keyType = 'string';
    protected static $logFillable = true;
    protected static $logOnlyDirty = true;

    // protected static $recordEvents = ['created', 'updated', 'deleting'];

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults();
    // }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, fn ($query, $search) => $query->search($search));
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = Uuid::uuid4()->toString();
            if ($model->hasUser && !$model->user_id) {
                $model->user_id = session()->has('impersonate') ? session()->get('impersonate') : auth()->id();
            }
            if ($model->hasHash && !$model->hash) {
                $model->hash = sha1($model->id . Str::random());
            }
            if ($model->hasNumber && !$model->number) {
                $number = DB::table($model->getTable())->max('number');
                $model->number = $number ? ((int) $number) + 1 : 1;
            }
        });
    }
}
