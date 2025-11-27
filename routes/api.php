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
    Route::apiResource('requisitions', ChefRequisitionController::class)->names([
        'index' => 'api.requisitions.index',
        'store' => 'api.requisitions.store',
        'show' => 'api.requisitions.show',
        'update' => 'api.requisitions.update',
        'destroy' => 'api.requisitions.destroy',
    ]);
    Route::post('requisitions/{chefRequisition}/approve', [ChefRequisitionController::class, 'approve'])
        ->name('api.requisitions.approve')
        ->middleware('role:manager,admin');
    Route::post('requisitions/{chefRequisition}/reject', [ChefRequisitionController::class, 'reject'])
        ->name('api.requisitions.reject')
        ->middleware('role:manager,admin');
    
    // Purchase Orders
    Route::apiResource('purchase-orders', PurchaseOrderController::class)->names([
        'index' => 'api.purchase-orders.index',
        'store' => 'api.purchase-orders.store',
        'show' => 'api.purchase-orders.show',
        'update' => 'api.purchase-orders.update',
        'destroy' => 'api.purchase-orders.destroy',
    ]);
    Route::post('purchase-orders/{purchaseOrder}/mark-purchased', [PurchaseOrderController::class, 'markPurchased'])
        ->name('api.purchase-orders.mark-purchased')
        ->middleware('role:purchaser,manager,admin');
    Route::get('purchase-orders/{purchaseOrder}/download-receipt', [PurchaseOrderController::class, 'downloadReceipt'])
        ->name('api.purchase-orders.download-receipt');
    
    // Expenses
    Route::apiResource('expenses', ExpenseController::class)->names([
        'index' => 'api.expenses.index',
        'store' => 'api.expenses.store',
        'show' => 'api.expenses.show',
        'update' => 'api.expenses.update',
        'destroy' => 'api.expenses.destroy',
    ]);
    Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])
        ->name('api.expenses.approve')
        ->middleware('role:manager,admin');
    Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])
        ->name('api.expenses.reject')
        ->middleware('role:manager,admin');
    
    // Payroll
    Route::apiResource('payroll', PayrollController::class)->names([
        'index' => 'api.payroll.index',
        'store' => 'api.payroll.store',
        'show' => 'api.payroll.show',
        'update' => 'api.payroll.update',
        'destroy' => 'api.payroll.destroy',
    ]);
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
