<?php
namespace App\Models;

use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;

class EntryItem extends Pivot
{
    use HasFactory, ActivityTrait;

    protected $table = 'entry_items';
    public $incrementing = false; // If you're using UUIDs here as well
    protected $keyType = 'string';

    protected $fillable = [
        'entry_id',
        'ledger_id',
        'amount',
        'narration',
        'dc',
    ];



    protected static function boot()
    {
        parent::boot();

        // Automatically generate UUIDs for new records
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }


    public function ledger()
    {
        return $this->belongsTo(Ledger::class);
    }

    public function entry()
    {
        return $this->belongsTo(Entry::class);
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['entry_id', 'ledger_id', 'amount', 'narration', 'dc', 'reconciliation_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

}
