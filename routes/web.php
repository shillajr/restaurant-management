<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\ItemController;

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
    Route::get('/purchase-orders/create', function () {
        return view('purchase-orders.create');
    })->name('purchase-orders.create');
    
    Route::post('/purchase-orders', function () {
        return "Purchase order created successfully - To be fully implemented";
    })->name('purchase-orders.store');
    
    Route::get('/purchase-orders/{purchaseOrder}', [\App\Http\Controllers\PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
    Route::patch('/purchase-orders/{purchaseOrder}/status', [\App\Http\Controllers\PurchaseOrderController::class, 'updateStatus'])->name('purchase-orders.update-status');
    
    Route::get('/expenses/create', function () {
        return view('expenses.create');
    })->name('expenses.create');
    
    Route::post('/expenses', function () {
        return "Expense created successfully - To be fully implemented";
    })->name('expenses.store');
    
    Route::get('/reports', function () {
        return 'Reports Index - To be implemented';
    })->name('reports.index');
    
    Route::get('/payroll/create', function () {
        return view('payroll.create');
    })->name('payroll.create');
    
    Route::post('/payroll', function () {
        return "Payroll processed successfully - To be fully implemented";
    })->name('payroll.store');
    
    Route::get('/settings', function () {
        return view('settings.index');
    })->name('settings');
    
    Route::put('/settings', function () {
        return "Settings updated successfully - To be fully implemented";
    })->name('settings.update');
    
    // Items Management routes
    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
    Route::post('/items', [ItemController::class, 'store'])->name('items.store');
    Route::put('/items/{id}', [ItemController::class, 'update'])->name('items.update');
    Route::delete('/items/{id}', [ItemController::class, 'destroy'])->name('items.destroy');
    
    // API endpoints for items
    Route::get('/api/items/active', [ItemController::class, 'getActiveItems'])->name('api.items.active');
    Route::get('/api/items/low-stock', [ItemController::class, 'getLowStockItems'])->name('api.items.low-stock');
});
