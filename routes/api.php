<?php

use App\Http\Controllers\ChefRequisitionController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\LoyverseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function () {
    
    // Chef Requisitions
    Route::apiResource('requisitions', ChefRequisitionController::class);
    Route::post('requisitions/{chefRequisition}/approve', [ChefRequisitionController::class, 'approve'])
        ->middleware('role:manager,admin');
    Route::post('requisitions/{chefRequisition}/reject', [ChefRequisitionController::class, 'reject'])
        ->middleware('role:manager,admin');
    
    // Purchase Orders
    Route::apiResource('purchase-orders', PurchaseOrderController::class);
    Route::post('purchase-orders/{purchaseOrder}/mark-purchased', [PurchaseOrderController::class, 'markPurchased'])
        ->middleware('role:purchaser,manager,admin');
    Route::get('purchase-orders/{purchaseOrder}/download-receipt', [PurchaseOrderController::class, 'downloadReceipt']);
    
    // Expenses
    Route::apiResource('expenses', ExpenseController::class);
    Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])
        ->middleware('role:manager,admin');
    Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])
        ->middleware('role:manager,admin');
    
    // Payroll
    Route::apiResource('payroll', PayrollController::class);
    Route::post('payroll/{payrollEntry}/mark-paid', [PayrollController::class, 'markPaid'])
        ->middleware('role:manager,admin');
    
    // Reports
    Route::prefix('reports')->group(function () {
        Route::get('daily-profit-loss', [ReportController::class, 'dailyProfitLoss'])
            ->middleware('role:manager,admin');
        Route::get('profit-loss-range', [ReportController::class, 'profitLossRange'])
            ->middleware('role:manager,admin');
        Route::get('payroll-summary', [ReportController::class, 'payrollSummary'])
            ->middleware('role:manager,admin');
        Route::get('export-expenses-csv', [ReportController::class, 'exportExpensesCSV'])
            ->middleware('role:manager,admin');
        Route::get('export-profit-loss-pdf', [ReportController::class, 'exportProfitLossPDF'])
            ->middleware('role:manager,admin');
        Route::get('export-payroll-pdf', [ReportController::class, 'exportPayrollPDF'])
            ->middleware('role:manager,admin');
    });
    
    // Loyverse Integration
    Route::prefix('loyverse')->group(function () {
        Route::post('sync-daily-sales', [LoyverseController::class, 'syncDailySales'])
            ->middleware('role:manager,admin');
        Route::post('import-csv', [LoyverseController::class, 'importCSV'])
            ->middleware('role:manager,admin');
        Route::get('verify-connection', [LoyverseController::class, 'verifyConnection'])
            ->middleware('role:manager,admin');
    });
});

// Loyverse Webhook (no authentication required)
Route::post('loyverse/webhook', [LoyverseController::class, 'webhook']);
