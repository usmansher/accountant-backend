<?php

declare(strict_types=1);

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\EntryTypeController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\Tenant\AccountController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'api',
])->prefix('api')->group(function () {
    Route::middleware(['auth:sanctum'])->group(function () {


        Route::prefix('dashboard')->group(function () {
            Route::get('/', [DashboardController::class, 'index']);
            Route::get('/income-expense-monthly-chart', [DashboardController::class, 'getIncomeExpenseMonthlyChart']);
            Route::get('/income-expense-chart', [DashboardController::class, 'getIncomeExpenseChart']);
        });

        Route::get('group/pre-requisite', [GroupController::class, 'preRequisite']);
        Route::apiResource('group', GroupController::class);
        Route::apiResource('ledger', LedgerController::class);
        Route::apiResource('tag', TagController::class);
        Route::apiResource('entry', EntryController::class);
        Route::apiResource('entrytypes', EntryTypeController::class);

        Route::get('chart-of-account', [AccountController::class, 'index']);

        Route::get('/reports/balancesheet', [ReportController::class, 'balanceSheet']);
        Route::get('/reports/profitloss', [ReportController::class, 'profitLoss']);
        Route::get('/reports/trialbalance', [ReportController::class, 'trialBalance']);
        Route::get('/reports/reconciliation', [ReportController::class, 'reconciliation']);
        Route::post('/reports/reconciliation/update', [ReportController::class, 'updateReconciliation']);


        Route::post('/importer/entries', [ImportController::class, 'importEntries']);



        Route::get('/ledger-list', [LedgerController::class, 'ledgerList']);

    });
});
