<?php

namespace App\Services;

use App\Models\Ledger;
use Illuminate\Support\Facades\DB;

/**
 * Class AccountList
 *
 * Stores the account tree structure with all related details.
 */
class AccountList
{
    // Properties
    public $id = 0;
    public $name = '';
    public $code = '';

    public $g_parent_id = 0;
    public $g_affects_gross = 0;
    public $l_group_id = 0;
    public $l_type = 0;
    public $l_reconciliation = 0;
    public $l_notes = '';

    public $op_total = 0;
    public $op_total_dc = 'D';
    public $dr_total = 0;
    public $cr_total = 0;
    public $cl_total = 0;
    public $cl_total_dc = 'D';

    public $children_groups = [];
    public $children_ledgers = [];

    public $only_opening = false;
    public $start_date = null;
    public $end_date = null;
    public $affects_gross = -1;

    // References to other services/models if needed
    // In CI you had $this->Group, $this->Ledger, $this->ledger_model, etc.
    // In Laravel you can inject or call them directly. For simplicity, we skip that here.

    /**
     * Class constructor.
     */
    public function __construct()
    {
        // In Laravel you typically do not need $this->_ci =& get_instance();
        // You can inject dependencies via the constructor if needed.
    }

    /**
     * Initialize the account tree from a given group ID.
     * @param int $id
     */
    public function start($id)
    {
        if ($id == 0) {
            $this->id = null;
            $this->name = "None";
        } else {
            // Example with Query Builder
            $group = DB::table('groups')->where('id', $id)->first();
            if ($group) {
                $this->id = $group->id;
                $this->name = $group->name;
                $this->code = $group->code;
                $this->g_parent_id = $group->parent_id;
                $this->g_affects_gross = $group->affects_gross;
            }
        }

        // Reset calculations
        $this->op_total = 0;
        $this->op_total_dc = 'D';
        $this->dr_total = 0;
        $this->cr_total = 0;
        $this->cl_total = 0;
        $this->cl_total_dc = 'D';

        // If affects_gross == 1, skip adding ledgers
        if ($this->affects_gross != 1) {
            $this->add_sub_ledgers();
        }

        // Always add sub-groups
        $this->add_sub_groups();
    }

    /**
     * Find and add sub-groups
     */
    public function add_sub_groups()
    {
        $conditions = [];
        $conditions[] = ['groups.parent_id', '=', $this->id];

        // Check net/gross restriction
        if ($this->affects_gross === 0) {
            $conditions[] = ['groups.affects_gross', '=', 0];
        } elseif ($this->affects_gross === 1) {
            $conditions[] = ['groups.affects_gross', '=', 1];
        }

        // Reset affects_gross below the first level
        $currentAffectsGross = $this->affects_gross;
        $this->affects_gross = -1;

        $child_group_q = DB::table('groups')
            ->where($conditions)
            ->orderBy('groups.code', 'asc')
            ->get();

        foreach ($child_group_q as $row) {
            $child = new AccountList();
            // Pass along relevant properties
            $child->only_opening = $this->only_opening;
            $child->start_date   = $this->start_date;
            $child->end_date     = $this->end_date;
            $child->affects_gross = -1; // no longer needed in sub groups

            $child->start($row->id);

            // Merge child group’s opening balance into current
            $temp1 = $this->calculate_withdc(
                $this->op_total,
                $this->op_total_dc,
                $child->op_total,
                $child->op_total_dc
            );
            $this->op_total    = $temp1['amount'];
            $this->op_total_dc = $temp1['dc'];

            // Merge child group’s closing balance into current
            $temp2 = $this->calculate_withdc(
                $this->cl_total,
                $this->cl_total_dc,
                $child->cl_total,
                $child->cl_total_dc
            );
            $this->cl_total    = $temp2['amount'];
            $this->cl_total_dc = $temp2['dc'];

            // Add Dr/Cr totals
            $this->dr_total += $child->dr_total;
            $this->cr_total += $child->cr_total;

            // Push the child object into the children array
            $this->children_groups[] = $child;
        }

        // Restore affects_gross for parent level if needed
        $this->affects_gross = $currentAffectsGross;
    }

    /**
     * Find and add sub-ledgers
     */
    public function add_sub_ledgers()
    {
        $child_ledger_q = DB::table('ledgers')
            ->where('ledgers.group_id', $this->id)
            ->orderBy('ledgers.code', 'asc')
            ->get();

        foreach ($child_ledger_q as $row) {
            $ledgerItem = [
                'id'               => $row->id,
                'name'             => $row->name,
                'code'             => $row->code,
                'l_group_id'       => $row->group_id,
                'l_type'           => $row->type,
                'l_reconciliation' => $row->reconciliation,
                'l_notes'          => $row->notes,
            ];

            // If start date is null, use opening balance
            if (is_null($this->start_date)) {
                $ledgerItem['op_total']    = $row->op_balance;
                $ledgerItem['op_total_dc'] = $row->op_balance_dc;
            } else {
                // Otherwise, opening balance is considered zero for the date range
                $ledgerItem['op_total']    = 0.00;
                $ledgerItem['op_total_dc'] = $row->op_balance_dc;
            }

            // Merge ledger’s opening balance into current group
            $temp3 = $this->calculate_withdc(
                $this->op_total,
                $this->op_total_dc,
                $ledgerItem['op_total'],
                $ledgerItem['op_total_dc']
            );
            $this->op_total    = $temp3['amount'];
            $this->op_total_dc = $temp3['dc'];

            // If only_opening is true, skip transaction-based totals
            if ($this->only_opening) {
                $ledgerItem['dr_total']    = 0;
                $ledgerItem['cr_total']    = 0;
                $ledgerItem['cl_total']    = $ledgerItem['op_total'];
                $ledgerItem['cl_total_dc'] = $ledgerItem['op_total_dc'];
            } else {
                // Here is where you'd use your ledger_model->closingBalance() equivalent.
                // For demo, let's assume we have a helper function getLedgerClosingBalance($ledgerId, $startDate, $endDate)
                $cl = $this->getLedgerClosingBalance($row->id, $this->start_date, $this->end_date);

                $ledgerItem['dr_total']    = $cl['dr_total'];
                $ledgerItem['cr_total']    = $cl['cr_total'];
                $ledgerItem['cl_total']    = $cl['amount'];
                $ledgerItem['cl_total_dc'] = $cl['dc'];
            }

            // Merge ledger’s closing balance into current group
            $temp4 = $this->calculate_withdc(
                $this->cl_total,
                $this->cl_total_dc,
                $ledgerItem['cl_total'],
                $ledgerItem['cl_total_dc']
            );
            $this->cl_total    = $temp4['amount'];
            $this->cl_total_dc = $temp4['dc'];

            // Accumulate Dr/Cr total
            $this->dr_total += $ledgerItem['dr_total'];
            $this->cr_total += $ledgerItem['cr_total'];

            // Finally, add this ledger item to the children_ledgers array
            $this->children_ledgers[] = $ledgerItem;
        }
    }

    /**
     * Calculate with Debit/Credit logic (placeholder version).
     */
    private function calculate_withdc($amt1, $dc1, $amt2, $dc2)
    {
        // Replace this with your own logic that was in functionscore->calculate_withdc(...)
        // Simplistic example:
        if ($dc1 === $dc2) {
            // same sign => sum
            $result = [
                'amount' => $amt1 + $amt2,
                'dc'     => $dc1
            ];
        } else {
            // different sign => subtract
            if ($amt1 > $amt2) {
                $result = [
                    'amount' => $amt1 - $amt2,
                    'dc'     => $dc1
                ];
            } elseif ($amt2 > $amt1) {
                $result = [
                    'amount' => $amt2 - $amt1,
                    'dc'     => $dc2
                ];
            } else {
                // Equal, so zero out
                $result = [
                    'amount' => 0,
                    'dc'     => 'D' // or 'C', whichever you prefer for zero
                ];
            }
        }
        return $result;
    }

    /**
     * Example helper to get ledger closing balance:
     */
    private function getLedgerClosingBalance($ledgerId, $startDate, $endDate)
    {
        // This is just a fake/stub method. In your real code, you’d replicate the logic from your ledger_model.
        // You could do something like:
        //   - sum all transactions from $startDate to $endDate for this ledger
        //   - figure out net DR/CR
        //   - return structure like [ 'dr_total' => x, 'cr_total' => y, 'amount' => z, 'dc' => 'D' or 'C' ]

        $ledger = Ledger::find($ledgerId);
        if (! $ledger) {
            return [
                'dr_total' => 0,
                'cr_total' => 0,
                'amount'   => 0,
                'dc'       => 'D'
            ];
        }else {
            $closingBalance = $ledger->getClosingBalance($startDate, $endDate);
            return $closingBalance;
        }

    }
}
