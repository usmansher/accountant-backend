<?php
namespace App\Services;

use App\Models\EntryItem;
use App\Models\EntryType;
use App\Models\Ledger;
use Exception;
use Illuminate\Support\Facades\Log;

class LedgerService
{

    var $restriction_bankcash = 1;


/**
     * Calculate closing balance of the specified ledger account for the given date range.
     *
     * @param int $id
     * @param string|null $startDate
     * @param string|null $endDate
     * @return array
     * @throws Exception
     */
    public function closingBalance($id, $startDate = null, $endDate = null)
    {
        $ledger = Ledger::find($id);
        Log::info('Ledger: ' . $ledger);

        if (!$ledger) {
            throw new Exception('Ledger not found. Failed to calculate closing balance.');
        }

        $opTotal = $ledger->op_balance ?? 0;
        $opTotalDc = $ledger->op_balance_dc;

        // Debit total
        $drTotal = EntryItem::where('ledger_id', $id)
            ->where('dc', 'D')
            ->when($startDate, function($query) use ($startDate) {
                return $query->whereHas('entry', function($query) use ($startDate) {
                    $query->where('date', '>=', $startDate);
                });
            })
            ->when($endDate, function($query) use ($endDate) {
                return $query->whereHas('entry', function($query) use ($endDate) {
                    $query->where('date', '<=', $endDate);
                });
            })
            ->sum('amount');

        // Credit total
        $crTotal = EntryItem::where('ledger_id', $id)
            ->where('dc', 'C')
            ->when($startDate, function($query) use ($startDate) {
                return $query->whereHas('entry', function($query) use ($startDate) {
                    $query->where('date', '>=', $startDate);
                });
            })
            ->when($endDate, function($query) use ($endDate) {
                return $query->whereHas('entry', function($query) use ($endDate) {
                    $query->where('date', '<=', $endDate);
                });
            })
            ->sum('amount');

        // Add opening balance
        $drTotalDc = $opTotalDc === 'D' ? $opTotal + $drTotal : $drTotal;
        $crTotalDc = $opTotalDc === 'C' ? $opTotal + $crTotal : $crTotal;

        // Calculate and update closing balance
        $cl = 0;
        $clDc = '';
        if ($drTotalDc > $crTotalDc) {
            $cl = $drTotalDc - $crTotalDc;
            $clDc = 'D';
        } elseif ($crTotalDc > $drTotalDc) {
            $cl = $crTotalDc - $drTotalDc;
            $clDc = 'C';
        } else {
            $cl = 0;
            $clDc = $opTotalDc;
        }

        return [
            'dc' => $clDc,
            'amount' => $cl,
            'dr_total' => $drTotal,
            'cr_total' => $crTotal
        ];
    }
}
