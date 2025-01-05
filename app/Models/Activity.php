<?php

namespace App\Models;
use Spatie\Activitylog\Models\Activity as ActivityModel;

class Activity extends ActivityModel {
    public static $columns = ['id', 'created_at', 'description', 'subject_id', 'subject_type', 'causer_id', 'causer_type'];
    public function user()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
