<?php

namespace App\Models;

use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;

class EntryType extends Model
{
    use HasFactory, ActivityTrait;
    protected $table = 'entrytypes';

    protected $keyType = 'string';
    public $incrementing = false; // Because we're using UUIDs


    protected static function boot()
    {
        parent::boot();

        // Automatically generate UUIDs for new records
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }



    protected $fillable = [
        'label', 'name', 'description', 'base_type', 'numbering', 'prefix', 'suffix', 'zero_padding', 'restriction_bankcash'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
