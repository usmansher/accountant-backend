<?php

namespace App\Http\Controllers;

use App\Helpers\AccountingHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ledger;
use App\Models\Account;
use App\Models\User;
use App\Models\Entry;
use App\Services\AccountTreeService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use PgSql\Lob;

class DashboardController extends Controller
{
    /**
     * Fetch Dashboard Data
     */
    public function index()
    {
        // Fetch ledgers of type 1 (Cash and Bank Summary)
        $ledgers = Ledger::where('type', 1)->get();

        // Compute closing balances
        $ledgersCB = $ledgers->map(function ($ledger) {
            return [
                'name' => $ledger->name,
                'code' => $ledger->code,
                'balance' => $this->closingBalance($ledger->id),
            ];
        });

        // Fetch Account Summary
        $accsummary = $this->getAccountSummary();

        // Fetch Dashboard Title
        $accountSettings = tenancy()->tenant;
        $dashboard_title = '<span style="font-weight: bolder; font-size: 34px !important;">' . $accountSettings->name . '</span><br><span>' . Carbon::parse($accountSettings->fy_start)->format('d M Y') . ' to ' . Carbon::parse($accountSettings->fy_end)->format('d M Y') . '</span>';

        // Prepare Response
        return response()->json([
            'ledgers' => $ledgersCB,
            'accsummary' => $accsummary,
            'dashboard_title' => $dashboard_title,
        ]);
    }


    /**
     * Helper Methods
     */

    /**
     * Calculate Closing Balance for a Ledger
     */
    private function closingBalance($ledgerId)
    {
        // Implement your closing balance logic here
        // Example: Sum of debits minus credits
        $dr_total = \App\Models\EntryItem::where('ledger_id', $ledgerId)
            ->where('dc', 'D')
            ->sum('amount');

        $cr_total = \App\Models\EntryItem::where('ledger_id', $ledgerId)
            ->where('dc', 'C')
            ->sum('amount');

        return $dr_total - $cr_total;
    }

    /**
     * Get Account Summary
     */
    private function getAccountSummary()
    {
        // Assets (Group ID 1)
        $assets = $this->getAccountTotal(1);

        // Liabilities (Group ID 2)
        $liabilities = $this->getAccountTotal(2);

        // Income (Group ID 3)
        $income = $this->getAccountTotal(3);

        // Expense (Group ID 4)
        $expense = $this->getAccountTotal(4);

        return [
            'assets_total_dc'        => $assets['total_dc'],
            'assets_total'           => $assets['total'],
            'liabilities_total_dc'   => $liabilities['total_dc'],
            'liabilities_total'      => $liabilities['total'],
            'income_total_dc'        => $income['total_dc'],
            'income_total'           => $income['total'],
            'expense_total_dc'       => $expense['total_dc'],
            'expense_total'          => $expense['total'],
        ];
    }

    /**
     * Get Account Total by Group
     */
    private function getAccountTotal($groupId)
    {
        // Assuming you have a 'groups' table and 'ledgers' have a 'group_id'
        $total_dc = Ledger::where('type', $groupId)
            ->withSum(['entryItems as dr_total' => function ($query) {
                $query->where('dc', 'D');
            }], 'amount')
            ->withSum(['entryItems as cr_total' => function ($query) {
                $query->where('dc', 'C');
            }], 'amount')
            ->get()
            ->sum('dr_total');

        $total = Ledger::where('type', $groupId)
            ->withSum(['entryItems as dr_total' => function ($query) {
                $query->where('dc', 'D');
            }], 'amount')
            ->withSum(['entryItems as cr_total' => function ($query) {
                $query->where('dc', 'C');
            }], 'amount')
            ->get()
            ->sum(function ($ledger) {
                return $ledger->dr_total - $ledger->cr_total;
            });

        return [
            'total_dc' => $total_dc,
            'total'    => $total,
        ];
    }

    /**
     * Build the tree structure recursively.
     */
    public function buildTree(array $elements, $parentId = 0)
    {
        $branch = [];
        foreach ($elements as $element) {
            if ($element->parent_id == $parentId) {
                $children = $this->buildTree($elements, $element->id);
                if ($children) {
                    $branch = array_merge($branch, $children);
                }
                $branch[] = $element->id;
            }
        }
        return $branch;
    }

   /**
     * Get total monthly for a specific group ID ($type).
     *
     * @param int $type - ID of the main group
     * @return array
     */
    public function getTotalMonthly($type)
    {
        // Get the current year
        $currentYear = Carbon::now()->year;

        // Fetch all groups
        $groups = DB::table('groups')->get();

        // Build the tree structure to get all descendant group IDs
        $groupIds = $this->buildTree($groups->toArray(), $type);

        // Fetch all ledger IDs under these groups
        $ledgerIds = DB::table('ledgers')
            ->whereIn('group_id', $groupIds)
            ->pluck('id')
            ->toArray();

        // Fetch monthly totals for the past 12 months for the specified group and its ledgers
        $monthlyTotals = DB::table('entry_items')
            ->join('entries', 'entry_items.entry_id', '=', 'entries.id')
            ->select(
                DB::raw('MONTH(entries.date) as month'),
                DB::raw('SUM(CASE WHEN entry_items.dc = "D" THEN entry_items.amount ELSE 0 END) as total_debit'),
                DB::raw('SUM(CASE WHEN entry_items.dc = "C" THEN entry_items.amount ELSE 0 END) as total_credit')
            )
            ->whereYear('entries.date', $currentYear)
            ->whereIn('entry_items.ledger_id', $ledgerIds)
            ->groupBy(DB::raw('MONTH(entries.date)'))
            ->orderBy(DB::raw('MONTH(entries.date)'))
            ->get()
            ->keyBy('month');

        // Prepare totals for all 12 months
        $totals = [];
        for ($i = 1; $i <= 12; $i++) {
            if (isset($monthlyTotals[$i])) {
                $totalDebit = $monthlyTotals[$i]->total_debit;
                $totalCredit = $monthlyTotals[$i]->total_credit;
                $totals[] = $totalDebit - $totalCredit;
            } else {
                $totals[] = 0;
            }
        }

        return $totals;
    }

    /**
     * Get Total Periodical
     * @param int $type - 3 for Income, 4 for Expense
     */
    private function getTotalPeriodical($type, $only_opening = false, $startDate = null, $endDate = null)
    {
        $accountService = new AccountTreeService($only_opening, $startDate, $endDate, -1);
        $liabilities = $accountService->getAccountTree($type); // group id 2 for Liabilities
        $liabilities_total = $liabilities['cl_total_dc'] == 'C'
            ? $liabilities['cl_total']
            : AccountingHelper::convertToPositive($liabilities['cl_total']);

        return $liabilities_total;
    }

    /**
     * Get Total of Today
     * @param int $type
     */
    private function getTotalOfToday($type)
    {
        return \App\Models\Entry::whereHas('entryType', function ($query) use ($type) {
                $query->where('id', $type);
            })
            ->whereDate('date', Carbon::today())
            ->sum('dr_total') - \App\Models\Entry::whereHas('entryType', function ($query) use ($type) {
                $query->where('id', $type);
            })
            ->whereDate('date', Carbon::today())
            ->sum('cr_total');
    }

    /**
     * Get Total of This Month
     * @param int $type
     */
    private function getTotalOfThisMonth($type)
    {
        return \App\Models\Entry::whereHas('entryType', function ($query) use ($type) {
                $query->where('id', $type);
            })
            ->whereMonth('date', Carbon::now()->month)
            ->sum('dr_total') - \App\Models\Entry::whereHas('entryType', function ($query) use ($type) {
                $query->where('id', $type);
            })
            ->whereMonth('date', Carbon::now()->month)
            ->sum('cr_total');
    }

    /**
     * Get Total Monthly Labels
     */
    private function getTotalMonthlyLabels()
    {
        return [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
    }


    /**
     * Fetch Income and Expense Monthly Chart Data
     */
    public function getIncomeExpenseMonthlyChart()
    {
        $currentYear = Carbon::now()->year;

        // Fetch monthly totals for Income (type 3) and Expense (type 4)
        $incomeData = $this->getTotalMonthly(3, $currentYear); // 3 represents Income
        $expenseData = $this->getTotalMonthly(4, $currentYear); // 4 represents Expense

        $total_income = array_sum($incomeData);
        $total_expense = array_sum($expenseData);

        Log::info('Total Income: ' . $total_income);
        Log::info('Total Expense: ' . $total_expense);

        // Calculate Net Worth
        $net_worth = $total_income - $total_expense;

        // Today's Income and Expense
        $today_income = $this->getTotalOfToday(3);
        $today_expense = $this->getTotalOfToday(4);

        // Current Month's Income and Expense
        $month_income = $this->getTotalOfThisMonth(3);
        $month_expense = $this->getTotalOfThisMonth(4);

        // Prepare xAxis Labels
        $xAxis = $this->getTotalMonthlyLabels($currentYear);

        // Prepare JSON response
        $json_ie_stats = [
            'Income'        => $incomeData,
            'Expense'       => $expenseData,
            'xAxis'         => $xAxis,
            'net_worth'     => $net_worth,
            'today_income'  => $today_income,
            'today_expense' => $today_expense,
            'month_income'  => $month_income,
            'month_expense' => $month_expense
        ];

        return response()->json($json_ie_stats);
    }

    /**
     * Fetch Income and Expense Overall Chart Data (Pie Chart)
     */
    public function getIncomeExpenseChart()
    {
        $assets = $this->getTotalPeriodical(1); // 3 represents Income
        $liabilities = $this->getTotalPeriodical(2); // 3 represents Income
        $income = $this->getTotalPeriodical(3); // 3 represents Income
        $expense = $this->getTotalPeriodical(4); // 4 represents Expense

        $json_ie_stats = [
            'Assets'  => $assets,
            'Liabilities' => $liabilities,
            'Income'  => $income,
            'Expense' => $expense,
        ];

        return response()->json($json_ie_stats);
    }
}
