<?php

namespace App\Http\Controllers;

use App\Models\ChefRequisition;
use App\Models\LoyverseSale;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard.
     */
    public function index()
    {
        $user = auth()->user();
        $today = Carbon::today();

        // Initialize variables
        $todaySales = 0;
        $salesCount = 0;
        $todayExpenses = 0;
        $expenseCount = 0;
        $todayProfit = 0;
        $profitMargin = 0;
        $pendingApprovals = 0;
        $recentRequisitions = collect();

        // Calculate KPIs for admin, manager, and finance roles
        if ($user->hasAnyRole(['admin', 'manager', 'finance'])) {
            // Today's Sales
            $salesData = LoyverseSale::whereDate('sale_date', $today)
                ->select(
                    DB::raw('COALESCE(SUM(total_amount), 0) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->first();

            $todaySales = $salesData->total ?? 0;
            $salesCount = $salesData->count ?? 0;

            // Today's Expenses
            $expenseData = Expense::whereDate('expense_date', $today)
                ->select(
                    DB::raw('COALESCE(SUM(amount), 0) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->first();

            $todayExpenses = $expenseData->total ?? 0;
            $expenseCount = $expenseData->count ?? 0;

            // Calculate Profit and Margin
            $todayProfit = $todaySales - $todayExpenses;
            $profitMargin = $todaySales > 0 ? ($todayProfit / $todaySales) * 100 : 0;

            // Pending Approvals Count
            $pendingApprovals = ChefRequisition::where('status', 'pending')->count();
        }

        // Recent Requisitions based on role
        if ($user->hasRole('chef')) {
            // Chefs see only their own requisitions
            $recentRequisitions = ChefRequisition::where('chef_id', $user->id)
                ->with('chef')
                ->latest()
                ->take(10)
                ->get();
        } elseif ($user->hasAnyRole(['admin', 'manager'])) {
            // Admin and managers see all requisitions
            $recentRequisitions = ChefRequisition::with('chef')
                ->latest()
                ->take(10)
                ->get();
        }

        return view('dashboard', compact(
            'todaySales',
            'salesCount',
            'todayExpenses',
            'expenseCount',
            'todayProfit',
            'profitMargin',
            'pendingApprovals',
            'recentRequisitions'
        ));
    }

    /**
     * Get dashboard stats via API.
     */
    public function stats()
    {
        $user = auth()->user();
        $today = Carbon::today();

        $stats = [
            'today_sales' => 0,
            'sales_count' => 0,
            'today_expenses' => 0,
            'expense_count' => 0,
            'today_profit' => 0,
            'profit_margin' => 0,
            'pending_approvals' => 0,
        ];

        if ($user->hasAnyRole(['admin', 'manager', 'finance'])) {
            $salesData = LoyverseSale::whereDate('sale_date', $today)
                ->select(
                    DB::raw('COALESCE(SUM(total_amount), 0) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->first();

            $stats['today_sales'] = $salesData->total ?? 0;
            $stats['sales_count'] = $salesData->count ?? 0;

            $expenseData = Expense::whereDate('expense_date', $today)
                ->select(
                    DB::raw('COALESCE(SUM(amount), 0) as total'),
                    DB::raw('COUNT(*) as count')
                )
                ->first();

            $stats['today_expenses'] = $expenseData->total ?? 0;
            $stats['expense_count'] = $expenseData->count ?? 0;

            $stats['today_profit'] = $stats['today_sales'] - $stats['today_expenses'];
            $stats['profit_margin'] = $stats['today_sales'] > 0 
                ? ($stats['today_profit'] / $stats['today_sales']) * 100 
                : 0;

            $stats['pending_approvals'] = ChefRequisition::where('status', 'pending')->count();
        }

        return response()->json($stats);
    }

    /**
     * Get recent activity for the dashboard.
     */
    public function recentActivity()
    {
        $user = auth()->user();

        $query = ChefRequisition::with('chef');

        if ($user->hasRole('chef')) {
            $query->where('chef_id', $user->id);
        }

        $requisitions = $query->latest()
            ->take(10)
            ->get()
            ->map(function ($requisition) {
                return [
                    'id' => $requisition->id,
                    'chef' => $requisition->chef->name,
                    'requested_date' => $requisition->requested_for_date->format('M d, Y'),
                    'items_count' => count($requisition->items),
                    'status' => $requisition->status,
                    'created_at' => $requisition->created_at->diffForHumans(),
                ];
            });

        return response()->json($requisitions);
    }
}
