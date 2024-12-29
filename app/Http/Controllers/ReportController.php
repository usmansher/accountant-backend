<?php

namespace App\Http\Controllers;

use App\Helpers\AccountingHelper;
use App\Models\Ledger;
use App\Services\AccountTreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportController extends Controller
{

    public function balanceSheet(Request $request)
    {
        $only_opening = $request->boolean('only_opening', false);
        $startDate = $request->input('startdate');
        $endDate = $request->input('enddate');

        // Convert dates if needed. Assume $startDate and $endDate are already in 'Y-m-d' or null.
        // Implement any date parsing logic here if required.

        // Fetch Liabilities
        $liabilitiesService = new AccountTreeService($only_opening, $startDate, $endDate, -1);
        $liabilities = $liabilitiesService->getAccountTree(2); // group id 2 for Liabilities
        $liabilities_total = $liabilities['cl_total_dc'] == 'C'
            ? $liabilities['cl_total']
            : AccountingHelper::convertToPositive($liabilities['cl_total']);

        // Fetch Assets
        $assetsService = new AccountTreeService($only_opening, $startDate, $endDate, -1);
        $assets = $assetsService->getAccountTree(1); // group id 1 for Assets
        $assets_total = $assets['cl_total_dc'] == 'D'
            ? $assets['cl_total']
            : AccountingHelper::convertToPositive($assets['cl_total']);

        // Fetch Income
        $incomeService = new AccountTreeService($only_opening, $startDate, $endDate, -1);
        $income = $incomeService->getAccountTree(3); // group id 3 for Income
        $income_total = $income['cl_total_dc'] == 'C'
            ? $income['cl_total']
            : AccountingHelper::convertToPositive($income['cl_total']);

        // Fetch Expense
        $expenseService = new AccountTreeService($only_opening, $startDate, $endDate, -1);
        $expense = $expenseService->getAccountTree(4); // group id 4 for Expenses
        $expense_total = $expense['cl_total_dc'] == 'D'
            ? $expense['cl_total']
            : AccountingHelper::convertToPositive($expense['cl_total']);

        // Calculate P&L
        $pandl = AccountingHelper::calculate($income_total, $expense_total, '-');

        // Opening difference
        $opdiff = $this->getOpeningDiff(); // Implement this method as per your logic
        $is_opdiff = !AccountingHelper::compare($opdiff['opdiff_balance'], 0, '==');

        // Adjust final totals
        $final_liabilities_total = $liabilities_total;
        $final_assets_total = $assets_total;

        // If net profit, add to liabilities, if net loss, add to assets
        if (AccountingHelper::compare($pandl, 0, '>=')) {
            $final_liabilities_total = AccountingHelper::calculate($final_liabilities_total, $pandl, '+');
        } else {
            $positive_pandl = AccountingHelper::convertToPositive($pandl);
            $final_assets_total = AccountingHelper::calculate($final_assets_total, $positive_pandl, '+');
        }

        // If difference in opening balance
        if ($is_opdiff) {
            if ($opdiff['opdiff_balance_dc'] == 'D') {
                $final_assets_total = AccountingHelper::calculate($final_assets_total, $opdiff['opdiff_balance'], '+');
            } else {
                $final_liabilities_total = AccountingHelper::calculate($final_liabilities_total, $opdiff['opdiff_balance'], '+');
            }
        }

        // Return JSON
        return response()->json([
            'only_opening' => $only_opening,
            'startdate' => $startDate,
            'enddate' => $endDate,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'assets_total' => $assets_total,
            'liabilities_total' => $liabilities_total,
            'pandl' => $pandl,
            'opdiff' => $opdiff,
            'is_opdiff' => $is_opdiff,
            'final_assets_total' => $final_assets_total,
            'final_liabilities_total' => $final_liabilities_total
        ]);
    }

    protected function getOpeningDiff()
    {
        // Implement your logic to fetch opening difference here.
        // Return array with 'opdiff_balance', 'opdiff_balance_dc'
        return [
            'opdiff_balance' => 0,
            'opdiff_balance_dc' => 'D'
        ];
    }

    public function profitLoss(Request $request)
    {
        $only_opening = $request->boolean('only_opening', false);
        $startDate = $request->input('startdate');
        $endDate = $request->input('enddate');

        // Implement any date conversion logic if required
        // e.g. if they come in d/m/Y format, convert to Y-m-d, etc.

        // Gross Expenses
        $grossExpensesService = new AccountTreeService($only_opening, $startDate, $endDate, 1);
        $gross_expenses = $grossExpensesService->getAccountTree(4); // 4 for Expenses group

        Log ::info('Gross Expenses: ' , [
            'gross_expenses' => $gross_expenses
        ]);
        $gross_expense_total = ($gross_expenses['cl_total_dc'] === 'D')
            ? $gross_expenses['cl_total']
            : AccountingHelper::convertToPositive($gross_expenses['cl_total']);

        // Gross Incomes
        $grossIncomesService = new AccountTreeService($only_opening, $startDate, $endDate, 1);
        $gross_incomes = $grossIncomesService->getAccountTree(3); // 3 for Income group
        $gross_income_total = ($gross_incomes['cl_total_dc'] === 'C')
            ? $gross_incomes['cl_total']
            : AccountingHelper::convertToPositive($gross_incomes['cl_total']);

        // Gross P/L
        $gross_pl = AccountingHelper::calculate($gross_income_total, $gross_expense_total, '-');

        // Net Expenses
        $netExpensesService = new AccountTreeService($only_opening, $startDate, $endDate, 0);
        $net_expenses = $netExpensesService->getAccountTree(4);
        $net_expense_total = ($net_expenses['cl_total_dc'] === 'D')
            ? $net_expenses['cl_total']
            : AccountingHelper::convertToPositive($net_expenses['cl_total']);

        // Net Incomes
        $netIncomesService = new AccountTreeService($only_opening, $startDate, $endDate, 0);
        $net_incomes = $netIncomesService->getAccountTree(3);
        $net_income_total = ($net_incomes['cl_total_dc'] === 'C')
            ? $net_incomes['cl_total']
            : AccountingHelper::convertToPositive($net_incomes['cl_total']);

        // Net P/L
        // net_pl = (net_income_total - net_expense_total) + gross_pl
        $net_pl = AccountingHelper::calculate($net_income_total, $net_expense_total, '-');
        $net_pl = AccountingHelper::calculate($net_pl, $gross_pl, '+');

        return response()->json([
            'only_opening' => $only_opening,
            'startdate' => $startDate,
            'enddate' => $endDate,
            'gross_expenses' => $gross_expenses,
            'gross_incomes' => $gross_incomes,
            'gross_expense_total' => $gross_expense_total,
            'gross_income_total' => $gross_income_total,
            'gross_pl' => $gross_pl,
            'net_expenses' => $net_expenses,
            'net_incomes' => $net_incomes,
            'net_expense_total' => $net_expense_total,
            'net_income_total' => $net_income_total,
            'net_pl' => $net_pl,
        ]);
    }


    public function trialBalance(Request $request)
    {
        // For trial balance, let's assume we want the entire FY
        // without start/end date filtering (as per your original code).
        $only_opening = false;
        $startDate = null;
        $endDate = null;
        $affectsGross = -1;

        $accountTreeService = new AccountTreeService($only_opening, $startDate, $endDate, $affectsGross);
        // Start from group_id = 0, meaning top-level or all groups
        // If your logic differs, adjust accordingly.
        $accountlist = $accountTreeService->getAccountTree(null);

        // We need totals for Dr and Cr:
        // The returned $accountlist will have aggregated totals of Dr, Cr, etc.
        $dr_total = $accountlist['dr_total'];
        $cr_total = $accountlist['cr_total'];

        // For subtitle, similar logic as before
        // Assuming we have $fy_start and $fy_end from some config or setting
        $fy_start = config('accounting.fy_start'); // Or however you get FY start
        $fy_end = config('accounting.fy_end');     // Or however you get FY end
        $subtitle = sprintf(
            'Trial Balance from %s to %s',
            AccountingHelper::formatDate($fy_start),
            AccountingHelper::formatDate($fy_end)
        );

        return response()->json([
            'title' => 'Trial Balance',
            'subtitle' => $subtitle,
            'accountlist' => $accountlist,
            'dr_total' => $dr_total,
            'cr_total' => $cr_total,
        ]);
    }
    //

    public function reconciliation(Request $request)
    {
        // Fetch ledgers that have reconciliation = 1
        $ledgers_q = Ledger::where('reconciliation', 1)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'code']);

        $ledgers = [0 => 'Please select a ledger'];
        foreach ($ledgers_q as $l) {
            $ledgers[$l->id] = AccountingHelper::toCodeWithName($l->code, $l->name);
        }

        // Check if we have parameters
        $ledgerId = $request->input('ledger_id');
        $startdate = $request->input('startdate');  // expected format: 'Y-m-d'
        $enddate = $request->input('enddate');      // expected format: 'Y-m-d'
        $showall = $request->input('showall');      // '0' or '1'

        if ($ledgerId && $ledgerId != 0) {
            $ledger = Ledger::find($ledgerId);
            if (!$ledger) {
                return response()->json(['status' => 'error', 'msg' => 'Ledger not found']);
            }

            // Convert dates if needed
            // If no start/end specified, assume fiscal year start/end
            $fy_start = config('accounting.fy_start'); // e.g. '2024-01-01'
            $fy_end = config('accounting.fy_end');     // e.g. '2024-12-31'

            $sd = $startdate ?: $fy_start;
            $ed = $enddate ?: $fy_end;

            // Compute opening balance, closing balance, reconciliation pending
            // Assume you have methods in Ledger model or a service class:
            $op = $ledger->getOpeningBalance($sd);            // returns ['amount' => float, 'dc' => 'D'/'C']
            $cl = $ledger->getClosingBalance($ed);            // returns ['amount' => float, 'dc' => 'D'/'C']
            $rp = $ledger->getReconciliationPending($sd, $ed); // returns ['dr_total' => float, 'cr_total' => float]

            $subtitle = sprintf(
                "Reconciliation for %s from %s to %s",
                $ledger->name,
                AccountingHelper::formatDate($sd),
                AccountingHelper::formatDate($ed)
            );

            $opening_title = sprintf('Opening balance as on %s', AccountingHelper::formatDate($sd));
            $closing_title = sprintf('Closing balance as on %s', AccountingHelper::formatDate($ed));
            $recpending_title = sprintf(
                'Reconciliation pending from %s to %s',
                AccountingHelper::formatDate($sd),
                AccountingHelper::formatDate($ed)
            );

            $entries = $ledger->getEntries($sd, $ed);

            return response()->json([
                'status' => 'success',
                'ledgers' => $ledgers,
                'showEntries' => true,
                'ledger_data' => [
                    'id' => $ledger->id,
                    'name' => $ledger->name,
                    'code' => $ledger->code,
                    'type' => $ledger->type ?? 0,    // if applicable
                    'notes' => $ledger->notes ?? ''
                ],
                'entries' => $entries,
                'subtitle' => $subtitle,
                'opening_title' => $opening_title,
                'closing_title' => $closing_title,
                'op' => $op,
                'cl' => $cl,
                'rp' => $rp,
                'recpending_title' => $recpending_title,
                'recpending_balance_d' => AccountingHelper::toCurrency('D', $rp['dr_total']),
                'recpending_balance_c' => AccountingHelper::toCurrency('C', $rp['cr_total']),
                'opening_balance' => AccountingHelper::toCurrency($op['dc'], $op['amount']),
                'closing_balance' => AccountingHelper::toCurrency($cl['dc'], $cl['amount']),
                'startdate' => $startdate,
                'enddate' => $enddate,
                'showall' => $showall,

            ]);
        } else {
            // If no ledger selected, just return ledgers list
            return response()->json([
                'status' => 'success',
                'ledgers' => $ledgers,
                'showEntries' => false
            ]);
        }
    }


    public function updateReconciliation(Request $request)
    {
        // The request should contain an array of reconciliation data.
        // For example: ReportRec = [
        //   [ 'id' => 'entry_item_id', 'recdate' => '2024-12-31' ],
        //   [ 'id' => 'another_entry_item_id', 'recdate' => '' ], // empty means no date
        // ]

        $data = $request->input('ReportRec', []);

        // Validate the data as needed
        // Example: 'ReportRec.*.id' => 'required|uuid', 'ReportRec.*.recdate' => 'nullable|date'

        foreach ($data as $recitem) {
            if (empty($recitem['id'])) {
                continue;
            }

            $recdate = null;
            if (!empty($recitem['recdate'])) {
                // Convert the date if needed
                // If the frontend sends 'YYYY-MM-DD', you may not need conversion.
                // If conversion is needed:
                // $recdate = Carbon::parse($recitem['recdate'])->format('Y-m-d');
                $recdate = $recitem['recdate'];
            }

            // Update the database
            DB::table('entry_items')
                ->where('id', $recitem['id'])
                ->update(['reconciliation_date' => $recdate]);
        }

        return response()->json(['status' => 'success', 'message' => 'Reconciliation updated successfully']);
    }
}
