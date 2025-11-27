<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\EmployeeSalaryController;
use App\Http\Controllers\EmployeeLoanController;

Route::get('/', function () {
    return redirect('/dashboard');
});

// Authentication routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Dashboard routes
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/activity', [DashboardController::class, 'recentActivity'])->name('dashboard.activity');
});

// Chef Requisitions routes
Route::middleware(['auth'])->group(function () {
    Route::resource('chef-requisitions', \App\Http\Controllers\ChefRequisitionController::class);
    
    // Approval workflow routes
    Route::post('/chef-requisitions/{chefRequisition}/approve', [\App\Http\Controllers\ChefRequisitionController::class, 'approve'])->name('chef-requisitions.approve');
    Route::post('/chef-requisitions/{chefRequisition}/reject', [\App\Http\Controllers\ChefRequisitionController::class, 'reject'])->name('chef-requisitions.reject');
    Route::post('/chef-requisitions/{chefRequisition}/request-changes', [\App\Http\Controllers\ChefRequisitionController::class, 'requestChanges'])->name('chef-requisitions.request-changes');
    
    // Purchase Orders routes
    Route::get('/purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
    Route::get('/purchase-orders/create', [\App\Http\Controllers\PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
    
    Route::post('/purchase-orders', [\App\Http\Controllers\PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
    
    Route::get('/purchase-orders/{purchaseOrder}', [\App\Http\Controllers\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::patch('/purchase-orders/{purchaseOrder}/status', [\App\Http\Controllers\PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.update-status');
    Route::post('/purchase-orders/{purchaseOrder}/approve', [\App\Http\Controllers\PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
    Route::post('/purchase-orders/{purchaseOrder}/reject', [\App\Http\Controllers\PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
    Route::post('/purchase-orders/{purchaseOrder}/return', [\App\Http\Controllers\PurchaseOrderController::class, 'returnForChanges'])->name('purchase-orders.return');
    
    Route::get('/expenses/create', function () {
        return view('expenses.create');
    })->name('expenses.create');
    
    Route::post('/expenses', function () {
        return "Expense created successfully - To be fully implemented";
    })->name('expenses.store');
    
    Route::get('/reports', function () {
        return 'Reports Index - To be implemented';
    })->name('reports.index');
    
    // Payroll Management Routes (Admin Only)
    Route::middleware(['role:admin'])->group(function () {
        // Payroll routes
        Route::get('/payroll', [PayrollController::class, 'index'])->name('payroll.index');
        Route::get('/payroll/create', [PayrollController::class, 'create'])->name('payroll.create');
        Route::post('/payroll', [PayrollController::class, 'store'])->name('payroll.store');
        Route::get('/payroll/preview', [PayrollController::class, 'preview'])->name('payroll.preview');
        Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
        Route::get('/payroll/{payroll}/payment/create', [PayrollController::class, 'createPayment'])->name('payroll.payment.create');
        Route::post('/payroll/{payroll}/payment', [PayrollController::class, 'makePayment'])->name('payroll.payment.store');
        
        // Employee Salary Management routes
        Route::get('/employees/salaries', [EmployeeSalaryController::class, 'index'])->name('employees.salary.index');
        Route::get('/employees/{user}/salary/edit', [EmployeeSalaryController::class, 'edit'])->name('employees.salary.edit');
        Route::put('/employees/{user}/salary', [EmployeeSalaryController::class, 'update'])->name('employees.salary.update');
        
        // Employee Loans routes
        Route::get('/loans', [EmployeeLoanController::class, 'index'])->name('loans.index');
        Route::get('/loans/create', [EmployeeLoanController::class, 'create'])->name('loans.create');
        Route::post('/loans', [EmployeeLoanController::class, 'store'])->name('loans.store');
        Route::get('/loans/{loan}', [EmployeeLoanController::class, 'show'])->name('loans.show');
        Route::post('/loans/{loan}/cancel', [EmployeeLoanController::class, 'cancel'])->name('loans.cancel');
    });
    
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    
    // Items Management routes
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::put('/items/{id}', [ItemController::class, 'update'])->name('items.update');
    Route::delete('/items/{id}', [ItemController::class, 'destroy'])->name('items.destroy');
    
    // API endpoints for items
    Route::get('/api/items/active', [ItemController::class, 'getActiveItems'])->name('api.items.active');
    
    // API endpoint for chef requisition details (for PO generation preview)
    Route::get('/api/chef-requisitions/{chefRequisition}', function (\App\Models\ChefRequisition $chefRequisition) {
        return response()->json($chefRequisition->load(['chef','purchaseOrder']));
    })->name('api.chef-requisitions.show');
    Route::get('/api/items/low-stock', [ItemController::class, 'getLowStockItems'])->name('api.items.low-stock');
});
