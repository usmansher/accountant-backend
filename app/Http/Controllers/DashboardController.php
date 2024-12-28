<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Ledger;
use App\Models\Account;
use App\Models\User;
use App\Models\Entry;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

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
     * Get Total Monthly
     * @param int $type - 3 for Income, 4 for Expense
     */
    private function getTotalMonthly($type)
    {
        // Fetch monthly totals for the past 12 months
        $currentYear = Carbon::now()->year;

        $monthlyTotals = \App\Models\Entry::select(DB::raw('MONTH(date) as month'), DB::raw('SUM(dr_total) as dr_total'), DB::raw('SUM(cr_total) as cr_total'))
            ->whereYear('date', $currentYear)
            ->whereHas('entryType', function ($query) use ($type) {
                $query->where('id', $type);
            })
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->keyBy('month');

        $totals = [];
        for ($i = 1; $i <= 12; $i++) {
            if (isset($monthlyTotals[$i])) {
                $dr = $monthlyTotals[$i]->dr_total ?? 0;
                $cr = $monthlyTotals[$i]->cr_total ?? 0;
                $totals[] = $dr - $cr;
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
    private function getTotalPeriodical($type)
    {
        // Implement based on your periodical logic
        // Placeholder example: Total Income or Expense
        return \App\Models\Entry::whereHas('entryType', function ($query) use ($type) {
                $query->where('id', $type);
            })
            ->sum('dr_total') - \App\Models\Entry::whereHas('entryType', function ($query) use ($type) {
                $query->where('id', $type);
            })
            ->sum('cr_total');
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
        $income = $this->getTotalPeriodical(3); // 3 represents Income
        $expense = $this->getTotalPeriodical(4); // 4 represents Expense

        $json_ie_stats = [
            'Income'  => $income,
            'Expense' => $expense,
        ];

        return response()->json($json_ie_stats);
    }
}
