<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\PayrollPayment;
use App\Models\User;
use App\Models\EmployeeLoan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Notifications\PaymentMadeNotification;
use Carbon\Carbon;

class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    /**
     * Display a listing of payrolls with dashboard summary.
     */
    public function index(Request $request)
    {
        // Dashboard Summary Statistics
        $totalEmployees = User::count();
        $totalMonthlySalaryObligations = User::sum('monthly_salary');
        $totalOutstandingDebts = Payroll::sum('outstanding_balance');
        $totalActiveLoans = EmployeeLoan::where('status', 'active')->sum('balance');
        
        // Calculate next cycle expected amount
        $totalExpectedNextCycle = $totalOutstandingDebts + $totalMonthlySalaryObligations;

        // Get payrolls with filters
        $query = Payroll::with(['employee', 'creator'])
            ->orderBy('month', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by month if provided
        if ($request->has('month') && $request->month) {
            $query->forMonth($request->month);
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->byStatus($request->status);
        }

        // Filter by employee if provided
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        $payrolls = $query->paginate(10)->appends($request->query());

        // Get list of employees for filter dropdown
        $employees = User::orderBy('name')->get();

        // Get unique months for filter dropdown (database-agnostic)
        $months = Payroll::select('month')
            ->distinct()
            ->orderBy('month', 'desc')
            ->get()
            ->map(function($payroll) {
                return Carbon::parse($payroll->month)->format('Y-m');
            })
            ->unique();

        return view('payroll.index', compact(
            'payrolls',
            'employees',
            'months',
            'totalEmployees',
            'totalMonthlySalaryObligations',
            'totalOutstandingDebts',
            'totalActiveLoans',
            'totalExpectedNextCycle'
        ));
    }

    /**
     * Show the form for creating a new payroll cycle.
     */
    public function create()
    {
        $employees = User::orderBy('name')->get();
        $currentMonth = Carbon::now()->startOfMonth()->format('Y-m-d');
        
        // Prepare employee data for Alpine.js
        $employeesData = $employees->map(function($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->name,
                'monthly_salary' => $emp->monthly_salary ?? 0,
                'daily_rate' => $emp->daily_rate ?? 0,
                'active_loans' => $emp->total_active_loan_balance ?? 0,
                'absent_days' => 0,
                'absent_deduction' => 0,
                'loan_deduction' => 0,
                'net_payable' => $emp->monthly_salary ?? 0
            ];
        })->values();
        
        return view('payroll.create', compact('employees', 'currentMonth', 'employeesData'));
    }

    /**
     * Store a newly created payroll cycle.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'month' => 'required|date',
            'employee_id' => 'nullable|exists:users,id',
            'absent_days' => 'nullable|array',
            'absent_days.*' => 'integer|min:0|max:31',
            'loan_deductions' => 'nullable|array',
            'loan_deductions.*' => 'numeric|min:0',
        ]);

        DB::beginTransaction();
        
        try {
            $month = Carbon::parse($validated['month'])->startOfMonth();
            $absentDays = $validated['absent_days'] ?? [];
            $loanDeductions = $validated['loan_deductions'] ?? [];

            // Determine which employees to process
            if ($request->employee_id) {
                $employees = User::where('id', $request->employee_id)->get();
            } else {
                $employees = User::all();
            }

            $created = 0;
            $skipped = 0;

            foreach ($employees as $employee) {
                // Check if payroll already exists for this employee and month
                $existing = Payroll::where('employee_id', $employee->id)
                    ->forMonth($month)
                    ->first();

                if ($existing) {
                    $skipped++;
                    continue;
                }

                // Get absent days for this employee
                $totalAbsentDays = $absentDays[$employee->id] ?? 0;

                // Calculate daily rate
                $dailyRate = $employee->monthly_salary / 30;

                // Calculate deductions
                $absentDaysDeduction = $totalAbsentDays * $dailyRate;
                $baseSalaryPayable = $employee->monthly_salary - $absentDaysDeduction;

                // Get loan deductions for this employee
                $loanDeduction = $loanDeductions[$employee->id] ?? 0;

                // Get previous outstanding balance
                $previousDebt = $this->getPreviousOutstandingBalance($employee->id, $month);

                // Calculate total due
                $totalDue = $baseSalaryPayable + $previousDebt - $loanDeduction;

                // Create payroll record
                $payroll = Payroll::create([
                    'employee_id' => $employee->id,
                    'month' => $month,
                    'monthly_salary' => $employee->monthly_salary,
                    'total_absent_days' => $totalAbsentDays,
                    'absent_days_deduction' => $absentDaysDeduction,
                    'base_salary_payable' => $baseSalaryPayable,
                    'loan_deductions' => $loanDeduction,
                    'previous_debt' => $previousDebt,
                    'total_due' => $totalDue,
                    'total_paid' => 0,
                    'outstanding_balance' => $totalDue,
                    'status' => 'pending',
                    'created_by' => Auth::id(),
                ]);

                // Process loan repayments if any
                if ($loanDeduction > 0) {
                    $this->processLoanRepayments($employee->id, $loanDeduction);
                }

                $created++;
            }

            DB::commit();

            return redirect()->route('payroll.index')
                ->with('success', "Payroll created successfully! Created: {$created}, Skipped (already exists): {$skipped}");

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create payroll: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified payroll with payment history.
     */
    public function show($id)
    {
        $payroll = Payroll::with(['employee', 'creator', 'payments.creator'])
            ->findOrFail($id);

        return view('payroll.show', compact('payroll'));
    }

    /**
     * Show the form for making a payment.
     */
    public function createPayment($payrollId)
    {
        $payroll = Payroll::with('employee')->findOrFail($payrollId);
        
        return view('payroll.make-payment', compact('payroll'));
    }

    /**
     * Record a payment against a payroll.
     */
    public function makePayment(Request $request, $payrollId)
    {
        $payroll = Payroll::findOrFail($payrollId);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $payroll->outstanding_balance,
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'send_notification' => 'nullable|boolean',
        ]);

        DB::beginTransaction();

        try {
            $payment = PayrollPayment::create([
                'payroll_id' => $payroll->id,
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'],
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);

            // Send notification if requested
            if ($request->has('send_notification') && $request->send_notification) {
                try {
                    $payroll->employee->notify(new PaymentMadeNotification($payment));
                    $payment->update(['notification_sent' => true]);
                } catch (\Exception $e) {
                    // Log but don't fail the payment
                    \Log::warning('Failed to send payment notification: ' . $e->getMessage());
                }
            }

            DB::commit();

            return redirect()->route('payroll.show', $payroll->id)
                ->with('success', 'Payment recorded successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to record payment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Calculate monthly payroll for all employees for a specific month.
     */
    public function calculateMonthlyPayroll($month)
    {
        $month = Carbon::parse($month)->startOfMonth();
        $employees = User::all();

        $summary = [
            'month' => $month->format('F Y'),
            'employees' => [],
            'totals' => [
                'monthly_salary' => 0,
                'absent_days_deduction' => 0,
                'base_salary_payable' => 0,
                'loan_deductions' => 0,
                'previous_debt' => 0,
                'total_due' => 0,
            ]
        ];

        foreach ($employees as $employee) {
            $dailyRate = $employee->monthly_salary / 30;
            
            $absentDays = 0;
            $absentDaysDeduction = $absentDays * $dailyRate;
            $baseSalaryPayable = $employee->monthly_salary - $absentDaysDeduction;
            $previousDebt = $this->getPreviousOutstandingBalance($employee->id, $month);
            $pendingLoans = $this->getPendingLoanBalance($employee->id);
            $totalDue = $baseSalaryPayable + $previousDebt;

            $employeeData = [
                'name' => $employee->name,
                'monthly_salary' => $employee->monthly_salary,
                'daily_rate' => $dailyRate,
                'absent_days' => $absentDays,
                'absent_days_deduction' => $absentDaysDeduction,
                'base_salary_payable' => $baseSalaryPayable,
                'previous_debt' => $previousDebt,
                'pending_loans' => $pendingLoans,
                'total_due' => $totalDue,
            ];

            $summary['employees'][] = $employeeData;

            $summary['totals']['monthly_salary'] += $employee->monthly_salary;
            $summary['totals']['absent_days_deduction'] += $absentDaysDeduction;
            $summary['totals']['base_salary_payable'] += $baseSalaryPayable;
            $summary['totals']['previous_debt'] += $previousDebt;
            $summary['totals']['total_due'] += $totalDue;
        }

        return $summary;
    }

    /**
     * Get previous outstanding balance for an employee before a specific month.
     */
    private function getPreviousOutstandingBalance($employeeId, $month)
    {
        return Payroll::where('employee_id', $employeeId)
            ->where('month', '<', $month)
            ->sum('outstanding_balance');
    }

    /**
     * Get pending loan balance for an employee.
     */
    private function getPendingLoanBalance($employeeId)
    {
        return EmployeeLoan::where('employee_id', $employeeId)
            ->where('status', 'active')
            ->sum('balance');
    }

    /**
     * Process loan repayments for an employee.
     */
    private function processLoanRepayments($employeeId, $totalDeduction)
    {
        $loans = EmployeeLoan::where('employee_id', $employeeId)
            ->where('status', 'active')
            ->where('balance', '>', 0)
            ->orderBy('issue_date', 'asc')
            ->get();

        $remainingDeduction = $totalDeduction;

        foreach ($loans as $loan) {
            if ($remainingDeduction <= 0) break;

            $repaymentAmount = min($remainingDeduction, $loan->balance);
            $loan->recordRepayment($repaymentAmount);
            $remainingDeduction -= $repaymentAmount;
        }
    }

    /**
     * Show payroll calculation preview for a specific month.
     */
    public function preview(Request $request)
    {
        $month = $request->input('month', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $summary = $this->calculateMonthlyPayroll($month);
        
        return view('payroll.preview', compact('summary', 'month'));
    }
}
