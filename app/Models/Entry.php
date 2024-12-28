<?php
namespace App\Models;

use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;

class Entry extends Model
{
    use HasFactory, ActivityTrait;
    protected $table = 'entries';
    protected $keyType = 'string';
    public $incrementing = false; // Because we're using UUIDs

    protected $fillable = [
        'id',
        'tag_id',
        'entrytype_id',
        'number',
        'date',
        'dr_total',
        'cr_total',
        'narration',
    ];

    protected static function boot()
    {
        parent::boot();

        // Automatically generate UUIDs for new records
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function ledgers()
    {
        return $this->belongsToMany(Ledger::class, 'entry_items', 'entry_id', 'ledger_id')
            ->withPivot('amount', 'dc');
    }


    public function items()
    {
        return $this->hasMany(EntryItem::class)->orderBy('created_at');
    }

    public function entryType()
    {
        return $this->belongsTo(EntryType::class, 'entrytype_id');
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tag_id', 'entrytype_id', 'number', 'date', 'dr_total', 'cr_total', 'narration'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

}
