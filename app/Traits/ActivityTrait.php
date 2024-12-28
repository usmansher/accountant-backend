<?php

namespace App\Traits;

use Spatie\Activitylog\Models\Activity as ModelsActivity;
use Spatie\Activitylog\Traits\LogsActivity;

trait ActivityTrait
{
    use LogsActivity;

    protected static $logFillable = true;

    protected static $logOnlyDirty = true;

    public function getDescriptionForEvent($event)
    {
        return static::getLogNameToUse() . " has been {$event}";
    }

    public function tapActivity(ModelsActivity $activity)
    {
        $user = auth()->user()->id ?? null;
        $activity->causer()->associate($user);
    }


    protected static function getLogNameToUse()
    {
        return (new \ReflectionClass(static::class))->getShortName();
    }
}
