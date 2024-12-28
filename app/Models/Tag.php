<?php

namespace App\Models;

use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class Tag extends Base
{
    use HasFactory, ActivityTrait;

    protected $fillable = [
        'id',
        'title',
        'color',
        'background',
    ];


    public function entries()
    {
        return $this->hasMany(Entry::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

}
