<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    private const CATEGORY_OPTIONS = [
        'Wages',
        'Rent / Lease Payments',
        'Utilities',
        'Equipment Purchase',
        'Repairs & Maintenance',
        'Restaurant Supplies',
        'Marketing & Advertising',
        'Licenses, Permits & Compliance',
        'Insurance',
        'Administrative',
        'Transportation',
        'Others',
    ];

    private const PAYMENT_METHOD_OPTIONS = [
        'Cash',
        'Card',
        'Bank Transfer',
        'Mobile Payment',
        'Check',
        'Other',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'category' => $request->input('category'),
            'amount_min' => $request->input('amount_min'),
            'amount_max' => $request->input('amount_max'),
        ];

        $expensesQuery = Expense::with(['creator', 'vendor'])
            ->orderByDesc('expense_date')
            ->orderByDesc('created_at');

        if (! empty($filters['date_from'])) {
            $expensesQuery->whereDate('expense_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $expensesQuery->whereDate('expense_date', '<=', $filters['date_to']);
        }

        if (! empty($filters['category']) && in_array($filters['category'], self::CATEGORY_OPTIONS, true)) {
            $expensesQuery->where('category', $filters['category']);
        }

        if (! empty($filters['amount_min']) && is_numeric($filters['amount_min'])) {
            $expensesQuery->where('amount', '>=', (float) $filters['amount_min']);
        }

        if (! empty($filters['amount_max']) && is_numeric($filters['amount_max'])) {
            $expensesQuery->where('amount', '<=', (float) $filters['amount_max']);
        }

        $expenses = $expensesQuery->paginate(15)->appends(array_filter($filters, static function ($value) {
            return ! is_null($value) && $value !== '';
        }));

        return view('expenses.index', [
            'expenses' => $expenses,
            'filters' => $filters,
            'categories' => self::CATEGORY_OPTIONS,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $vendors = Vendor::orderBy('name')->get(['id', 'name']);

        return view('expenses.create', [
            'categories' => self::CATEGORY_OPTIONS,
            'paymentMethods' => self::PAYMENT_METHOD_OPTIONS,
            'vendors' => $vendors,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $this->validateExpense($request);

        $vendor = null;
        if (! empty($validated['vendor_id'])) {
            $vendor = Vendor::find($validated['vendor_id']);
        }

        $receiptPath = null;
        if ($request->hasFile('proof')) {
            $receiptPath = $request->file('proof')->store('expenses', 'public');
        }

        $amount = round($validated['quantity'] * $validated['unit_price'], 2);

        Expense::create([
            'category' => $validated['category'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'] ?? '',
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'amount' => $amount,
            'vendor_id' => $vendor?->id,
            'vendor' => $vendor?->name,
            'payment_method' => $validated['payment_method'],
            'invoice_number' => $validated['invoice_number'] ?? null,
            'receipt_path' => $receiptPath,
            'note' => $validated['note'] ?? null,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Expense recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $expense = Expense::with('creator')->findOrFail($id);
        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $expense = Expense::findOrFail($id);
        $vendors = Vendor::orderBy('name')->get(['id', 'name']);

        return view('expenses.edit', [
            'expense' => $expense,
            'categories' => self::CATEGORY_OPTIONS,
            'paymentMethods' => self::PAYMENT_METHOD_OPTIONS,
            'vendors' => $vendors,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $expense = Expense::findOrFail($id);

        $validated = $this->validateExpense($request);

        $vendor = null;
        if (! empty($validated['vendor_id'])) {
            $vendor = Vendor::find($validated['vendor_id']);
        }

        $receiptPath = $expense->receipt_path;
        if ($request->hasFile('proof')) {
            if ($receiptPath) {
                Storage::disk('public')->delete($receiptPath);
            }
            $receiptPath = $request->file('proof')->store('expenses', 'public');
        }

        $amount = round($validated['quantity'] * $validated['unit_price'], 2);

        $expense->update([
            'category' => $validated['category'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'] ?? '',
            'item_name' => $validated['item_name'],
            'quantity' => $validated['quantity'],
            'unit_price' => $validated['unit_price'],
            'amount' => $amount,
            'vendor_id' => $vendor?->id,
            'vendor' => $vendor?->name,
            'payment_method' => $validated['payment_method'],
            'invoice_number' => $validated['invoice_number'] ?? null,
            'receipt_path' => $receiptPath,
            'note' => $validated['note'] ?? null,
        ]);

        return redirect()
            ->route('expenses.show', $expense)
            ->with('success', 'Expense updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $expense = Expense::findOrFail($id);

        // Delete receipt if exists
        if ($expense->receipt_path) {
            Storage::disk('public')->delete($expense->receipt_path);
        }

        $expense->delete();

        return redirect()
            ->route('dashboard')
            ->with('success', 'Expense deleted successfully');
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', Rule::in(self::CATEGORY_OPTIONS)],
            'item_name' => ['required', 'string', 'max:255'],
            'quantity' => ['required', 'numeric', 'min:0.01'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'vendor_id' => ['nullable', 'exists:vendors,id'],
            'payment_method' => ['required', Rule::in(self::PAYMENT_METHOD_OPTIONS)],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:1000'],
            'proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
        ]);
    }
}
