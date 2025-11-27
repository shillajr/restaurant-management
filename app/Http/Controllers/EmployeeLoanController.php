<?php

namespace App\Http\Controllers;

use App\Models\EmployeeLoan;
use App\Models\User;
use App\Models\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EmployeeLoanController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:admin');
    }

    /**
     * Display a listing of employee loans.
     */
    public function index(Request $request)
    {
        $query = EmployeeLoan::with('employee');

        // Filter by employee if provided
        if ($request->has('employee_id') && $request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        $loans = $query->orderBy('issue_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->appends($request->query());

        // Summary statistics
        $activeLoansCount = EmployeeLoan::where('status', 'active')->count();
        $totalActiveBalance = EmployeeLoan::where('status', 'active')->sum('balance');
        $totalIssued = EmployeeLoan::sum('amount');
        $totalRepaid = EmployeeLoan::sum('total_repaid');
        $completedLoansCount = EmployeeLoan::where('status', 'completed')->count();

        // Get employees for filter
        $employees = User::orderBy('name')->get();

        return view('loans.index', compact(
            'loans',
            'employees',
            'activeLoansCount',
            'totalActiveBalance',
            'totalIssued',
            'totalRepaid',
            'completedLoansCount'
        ));
    }

    /**
     * Show the form for creating a new loan.
     */
    public function create()
    {
        $employees = User::orderBy('name')->get();
        
        return view('loans.create', compact('employees'));
    }

    /**
     * Store a newly created loan.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'purpose' => 'required|string|max:255',
            'issue_date' => 'required|date',
            'repayment_per_cycle' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $loan = EmployeeLoan::create([
                'employee_id' => $validated['employee_id'],
                'amount' => $validated['amount'],
                'purpose' => $validated['purpose'],
                'issue_date' => $validated['issue_date'],
                'repayment_per_cycle' => $validated['repayment_per_cycle'],
                'total_repaid' => 0,
                'balance' => $validated['amount'],
                'status' => 'active',
                'notes' => $validated['notes'],
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()->route('loans.show', $loan->id)
                ->with('success', 'Employee loan created successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to create loan: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified loan with repayment history.
     */
    public function show($id)
    {
        $loan = EmployeeLoan::with(['employee', 'creator'])->findOrFail($id);

        // Get payroll records that included deductions for this loan
        $repayments = Payroll::where('employee_id', $loan->employee_id)
            ->where('loan_deductions', '>', 0)
            ->where('month', '>=', $loan->issue_date)
            ->orderBy('month', 'desc')
            ->get();

        return view('loans.show', compact('loan', 'repayments'));
    }

    /**
     * Cancel an active loan.
     */
    public function cancel($id)
    {
        $loan = EmployeeLoan::findOrFail($id);

        if ($loan->status !== 'active') {
            return redirect()->back()
                ->with('error', 'Only active loans can be cancelled.');
        }

        $loan->update(['status' => 'cancelled']);

        return redirect()->route('loans.index')
            ->with('success', 'Loan cancelled successfully.');
    }
}
