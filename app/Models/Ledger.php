<?php

namespace App\Models;

use App\Traits\ActivityTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;

class Ledger extends Model
{
    use ActivityTrait;

    protected $table = 'ledgers';
    protected $keyType = 'string';
    public $incrementing = false; // Because we're using UUIDs

    protected $fillable = [
        'id',
        'group_id',
        'name',
        'code',
        'op_balance',
        'op_balance_dc',
        'type',
        'reconciliation',
        'notes',
    ];

    protected static function boot()
    {
        parent::boot();

        // Automatically generate UUIDs for new records
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'group_id');
    }

    public function entries()
    {
        return $this->belongsToMany(Entry::class, 'entry_items', 'ledger_id', 'entry_id')
            ->withPivot('amount', 'dc');
    }


    public function entryItems()
    {
        return $this->hasMany(EntryItem::class, 'ledger_id');
    }


    public function getOpeningBalance($startDate = null, $endDate = null)
    {
        $drTotal = 0.00;
        $crTotal = 0.00;

        $query = $this->entries();

        if ($startDate) {
            $query->where('date', '<', $startDate);
        }

        $entries = $query->get();

        foreach ($entries as $entry) {
            $amount = $entry->pivot->amount;
            $dc = $entry->pivot->dc;

            if ($dc == 'D') {
                $drTotal += $amount;
            } else {
                $crTotal += $amount;
            }
        }

        $balance = $this->calculateOpeningBalance($drTotal, $crTotal);

        return [
            'amount' => $balance['amount'],
            'dc' => $balance['dc'],
            'dr_total' => $drTotal,
            'cr_total' => $crTotal,
        ];
    }

    public function calculateOpeningBalance($drTotal, $crTotal)
    {
        $opBalance = $this->op_balance;
        $opBalanceDc = $this->op_balance_dc;

        if ($opBalanceDc == 'D') {
            $amount = $opBalance + $drTotal - $crTotal;
            $dc = $amount >= 0 ? 'D' : 'C';
        } else {
            $amount = $opBalance + $crTotal - $drTotal;
            $dc = $amount >= 0 ? 'C' : 'D';
        }

        return [
            'amount' => abs($amount),
            'dc' => $dc,
        ];
    }

    /**
     * Get the closing balance for the ledger.
     */
    public function getClosingBalance($startDate = null, $endDate = null)
    {
        $drTotal = 0.00;
        $crTotal = 0.00;

        $query = $this->entries();

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        $entries = $query->get();

        foreach ($entries as $entry) {
            $amount = $entry->pivot->amount;
            $dc = $entry->pivot->dc;

            if ($dc == 'D') {
                $drTotal += $amount;
            } else {
                $crTotal += $amount;
            }
        }

        $balance = $this->calculateClosingBalance($drTotal, $crTotal);

        return [
            'amount'    => $balance['amount'],
            'dc'        => $balance['dc'],
            'dr_total'  => $drTotal,
            'cr_total'  => $crTotal,
            'cl_total'  => $balance['amount'],
            'cl_total_dc' => $balance['dc'],
        ];
    }


    /**
     * Calculate the closing balance based on totals and opening balance.
     */
    private function calculateClosingBalance($drTotal, $crTotal)
    {
        $opBalance = $this->op_balance;
        $opBalanceDc = $this->op_balance_dc;

        if ($opBalanceDc == 'D') {
            $amount = $opBalance + $drTotal - $crTotal;
            $dc = $amount >= 0 ? 'D' : 'C';
        } else {
            $amount = $opBalance + $crTotal - $drTotal;
            $dc = $amount >= 0 ? 'C' : 'D';
        }

        return [
            'amount' => abs($amount),
            'dc' => $dc,
        ];
    }

    public function ledgers()
    {
        return $this->belongsToMany(Ledger::class, 'entry_items', 'entry_id', 'ledger_id')
            ->withPivot('amount', 'dc', 'reconciliation_date');
    }



    /**
     * Get the total unreconciled (reconciliation_date = NULL) debit and credit
     * amounts for this ledger between optional start and end dates.
     */
    public function getReconciliationPending($start_date = null, $end_date = null)
    {
        // Debit total
        $drQuery = DB::table('entry_items')
            ->join('entries', 'entries.id', '=', 'entry_items.entry_id')
            ->where('entry_items.ledger_id', $this->id)
            ->where('entry_items.dc', 'D')
            ->whereNull('entry_items.reconciliation_date');

        if (!is_null($start_date)) {
            $drQuery->where('entries.date', '>=', $start_date);
        }
        if (!is_null($end_date)) {
            $drQuery->where('entries.date', '<=', $end_date);
        }

        $dr_total = $drQuery->sum('entry_items.amount');

        // Credit total
        $crQuery = DB::table('entry_items')
            ->join('entries', 'entries.id', '=', 'entry_items.entry_id')
            ->where('entry_items.ledger_id', $this->id)
            ->where('entry_items.dc', 'C')
            ->whereNull('entry_items.reconciliation_date');

        if (!is_null($start_date)) {
            $crQuery->where('entries.date', '>=', $start_date);
        }
        if (!is_null($end_date)) {
            $crQuery->where('entries.date', '<=', $end_date);
        }

        $cr_total = $crQuery->sum('entry_items.amount');

        return [
            'dr_total' => $dr_total ?: 0,
            'cr_total' => $cr_total ?: 0
        ];
    }


    /**
     * Get the total reconciled (reconciliation_date != NULL) debit and credit
     * amounts for this ledger between optional start and end dates.
     */
    public function getEntries($startDate = null, $endDate = null)
    {
        // Start with the base query for entries related to this Ledger
        $query = $this->entries()
            ->where('entry_items.ledger_id', $this->id) // Ensure filtering by this ledger_id
            ->selectRaw('entries.date, entries.number, entries.tag_id, entries.entrytype_id, entry_items.*, entry_items.id')
            ->with('tag', 'entryType', 'ledgers');

        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }

        return $query->get();
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
