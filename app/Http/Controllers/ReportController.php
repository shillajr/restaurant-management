<?php

namespace App\Http\Controllers;

use App\Models\LoyverseSale;
use App\Models\Expense;
use App\Models\PayrollEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    /**
     * Get daily profit and loss report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function dailyProfitLoss(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));
        
        $sales = LoyverseSale::where('date', $date)->sum('total_sales');
        $salesTax = LoyverseSale::where('date', $date)->sum('tax');
        
        $expenses = Expense::whereDate('date', $date)
            ->whereNotNull('approved_at')
            ->sum('amount');
        
        $expensesByCategory = Expense::whereDate('date', $date)
            ->whereNotNull('approved_at')
            ->selectRaw('ledger_code, SUM(amount) as total')
            ->groupBy('ledger_code')
            ->get();
        
        $profitLoss = $sales - $expenses;
        $margin = $sales > 0 ? ($profitLoss / $sales) * 100 : 0;

        $data = [
            'date' => $date,
            'sales' => round($sales, 2),
            'sales_tax' => round($salesTax, 2),
            'expenses' => round($expenses, 2),
            'expenses_by_category' => $expensesByCategory,
            'profit_loss' => round($profitLoss, 2),
            'margin_percent' => round($margin, 2)
        ];

        if ($request->wantsJson() || $request->has('json')) {
            return response()->json($data);
        }

        return view('reports.daily-profit-loss', $data);
    }

    /**
     * Get profit and loss report for a date range
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function profitLossRange(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        $sales = LoyverseSale::whereBetween('date', [$startDate, $endDate])->sum('total_sales');
        $salesTax = LoyverseSale::whereBetween('date', [$startDate, $endDate])->sum('tax');
        
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('approved_at')
            ->sum('amount');
        
        $expensesByCategory = Expense::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('approved_at')
            ->selectRaw('ledger_code, SUM(amount) as total')
            ->groupBy('ledger_code')
            ->get();

        $dailyBreakdown = LoyverseSale::whereBetween('date', [$startDate, $endDate])
            ->selectRaw('date, SUM(total_sales) as sales')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) use ($startDate, $endDate) {
                $dayExpenses = Expense::whereDate('date', $item->date)
                    ->whereNotNull('approved_at')
                    ->sum('amount');
                
                return [
                    'date' => $item->date,
                    'sales' => round($item->sales, 2),
                    'expenses' => round($dayExpenses, 2),
                    'profit_loss' => round($item->sales - $dayExpenses, 2)
                ];
            });
        
        $profitLoss = $sales - $expenses;
        $margin = $sales > 0 ? ($profitLoss / $sales) * 100 : 0;

        $data = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sales' => round($sales, 2),
            'sales_tax' => round($salesTax, 2),
            'expenses' => round($expenses, 2),
            'expenses_by_category' => $expensesByCategory,
            'profit_loss' => round($profitLoss, 2),
            'margin_percent' => round($margin, 2),
            'daily_breakdown' => $dailyBreakdown
        ];

        if ($request->wantsJson() || $request->has('json')) {
            return response()->json($data);
        }

        return view('reports.profit-loss-range', $data);
    }

    /**
     * Get payroll summary report
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\View\View
     */
    public function payrollSummary(Request $request)
    {
        $weekStart = $request->get('week_start', today()->startOfWeek()->format('Y-m-d'));
        $weekEnd = $request->get('week_end', today()->endOfWeek()->format('Y-m-d'));

        $payrollData = PayrollEntry::with('employee')
            ->whereBetween('week_start', [$weekStart, $weekEnd])
            ->get();

        $totalDue = $payrollData->sum('salary_due');
        $totalPaid = $payrollData->sum('salary_paid');
        $outstanding = $totalDue - $totalPaid;

        $summary = [
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'total_due' => round($totalDue, 2),
            'total_paid' => round($totalPaid, 2),
            'outstanding' => round($outstanding, 2),
            'employee_count' => $payrollData->count(),
            'employees' => $payrollData->map(function($entry) {
                return [
                    'employee' => $entry->employee->name,
                    'employee_id' => $entry->employee_id,
                    'due' => round($entry->salary_due, 2),
                    'paid' => round($entry->salary_paid, 2),
                    'outstanding' => round($entry->salary_due - $entry->salary_paid, 2),
                    'status' => $entry->salary_paid >= $entry->salary_due ? 'paid' : 'pending',
                    'week_start' => $entry->week_start,
                    'week_end' => $entry->week_end
                ];
            })
        ];

        if ($request->wantsJson() || $request->has('json')) {
            return response()->json($summary);
        }

        return view('reports.payroll-summary', $summary);
    }

    /**
     * Export expenses to CSV
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportExpensesCSV(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        $expenses = Expense::with(['approvedBy'])
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('approved_at')
            ->orderBy('date')
            ->get();

        $filename = 'expenses_' . $startDate . '_to_' . $endDate . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($expenses) {
            $file = fopen('php://output', 'w');
            
            // CSV Header
            fputcsv($file, [
                'ID',
                'Date',
                'Ledger Code',
                'Description',
                'Amount',
                'Approved By',
                'Approved At',
                'Receipt URL'
            ]);

            // Data rows
            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->id,
                    $expense->date,
                    $expense->ledger_code,
                    $expense->description ?? '',
                    $expense->amount,
                    $expense->approvedBy ? $expense->approvedBy->name : '',
                    $expense->approved_at ? $expense->approved_at->format('Y-m-d H:i:s') : '',
                    $expense->receipt_url ?? ''
                ]);
            }

            fclose($file);
        };

        activity()
            ->withProperties(['start_date' => $startDate, 'end_date' => $endDate])
            ->log('Expenses exported to CSV');

        return Response::stream($callback, 200, $headers);
    }

    /**
     * Export profit/loss report to PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportProfitLossPDF(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        $sales = LoyverseSale::whereBetween('date', [$startDate, $endDate])->sum('total_sales');
        $salesTax = LoyverseSale::whereBetween('date', [$startDate, $endDate])->sum('tax');
        
        $expenses = Expense::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('approved_at')
            ->sum('amount');
        
        $expensesByCategory = Expense::whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('approved_at')
            ->selectRaw('ledger_code, SUM(amount) as total')
            ->groupBy('ledger_code')
            ->get();

        $profitLoss = $sales - $expenses;
        $margin = $sales > 0 ? ($profitLoss / $sales) * 100 : 0;

        $data = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'sales' => round($sales, 2),
            'sales_tax' => round($salesTax, 2),
            'expenses' => round($expenses, 2),
            'expenses_by_category' => $expensesByCategory,
            'profit_loss' => round($profitLoss, 2),
            'margin_percent' => round($margin, 2),
            'generated_at' => now()->format('Y-m-d H:i:s')
        ];

        activity()
            ->withProperties(['start_date' => $startDate, 'end_date' => $endDate])
            ->log('Profit/Loss report exported to PDF');

        $pdf = Pdf::loadView('reports.pdf.profit-loss', $data);
        
        return $pdf->download('profit_loss_' . $startDate . '_to_' . $endDate . '.pdf');
    }

    /**
     * Export payroll report to PDF
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function exportPayrollPDF(Request $request)
    {
        $weekStart = $request->get('week_start', today()->startOfWeek()->format('Y-m-d'));
        $weekEnd = $request->get('week_end', today()->endOfWeek()->format('Y-m-d'));

        $payrollData = PayrollEntry::with('employee')
            ->whereBetween('week_start', [$weekStart, $weekEnd])
            ->get();

        $data = [
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
            'total_due' => round($payrollData->sum('salary_due'), 2),
            'total_paid' => round($payrollData->sum('salary_paid'), 2),
            'outstanding' => round($payrollData->sum('salary_due') - $payrollData->sum('salary_paid'), 2),
            'payroll_entries' => $payrollData,
            'generated_at' => now()->format('Y-m-d H:i:s')
        ];

        activity()
            ->withProperties(['week_start' => $weekStart, 'week_end' => $weekEnd])
            ->log('Payroll report exported to PDF');

        $pdf = Pdf::loadView('reports.pdf.payroll', $data);
        
        return $pdf->download('payroll_' . $weekStart . '_to_' . $weekEnd . '.pdf');
    }
}
