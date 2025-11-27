<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenses = Expense::with('creator')
            ->latest()
            ->paginate(15);

        return view('expenses.index', compact('expenses'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('expenses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.invoice_number' => 'nullable|string',
            'items.*.payment_method' => 'nullable|string',
            'items.*.description' => 'nullable|string',
            'items.*.receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Process items to include item names, vendors, and per-item details
        $processedItems = [];
        $totalAmount = 0;

        foreach ($validated['items'] as $index => $itemData) {
            $item = Item::find($itemData['item_id']);
            
            $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
            $totalAmount += $lineTotal;

            // Handle per-item receipt upload
            $receiptPath = null;
            if ($request->hasFile("items.{$index}.receipt")) {
                $receiptPath = $request->file("items.{$index}.receipt")->store('receipts', 'public');
            }

            $processedItems[] = [
                'item_id' => $item->id,
                'name' => $item->name,
                'vendor' => $item->vendor,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'line_total' => $lineTotal,
                'invoice_number' => $itemData['invoice_number'] ?? null,
                'payment_method' => $itemData['payment_method'] ?? null,
                'description' => $itemData['description'] ?? null,
                'receipt_path' => $receiptPath,
            ];
        }

        // Create the expense
        $expense = Expense::create([
            'category' => $validated['category'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'],
            'amount' => $totalAmount,
            'items' => $processedItems,
            'created_by' => Auth::id(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'Expense created successfully with ' . count($processedItems) . ' items totaling $' . number_format($totalAmount, 2));
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
        return view('expenses.edit', compact('expense'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'category' => 'required|string',
            'expense_date' => 'required|date',
            'description' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0.01',
            'items.*.invoice_number' => 'nullable|string',
            'items.*.payment_method' => 'nullable|string',
            'items.*.description' => 'nullable|string',
            'items.*.receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'amount' => 'required|numeric|min:0.01',
        ]);

        // Process items
        $processedItems = [];
        $totalAmount = 0;

        foreach ($validated['items'] as $index => $itemData) {
            $item = Item::find($itemData['item_id']);
            
            $lineTotal = $itemData['quantity'] * $itemData['unit_price'];
            $totalAmount += $lineTotal;

            // Handle per-item receipt upload
            $receiptPath = null;
            if ($request->hasFile("items.{$index}.receipt")) {
                $receiptPath = $request->file("items.{$index}.receipt")->store('receipts', 'public');
            }

            $processedItems[] = [
                'item_id' => $item->id,
                'name' => $item->name,
                'vendor' => $item->vendor,
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'line_total' => $lineTotal,
                'invoice_number' => $itemData['invoice_number'] ?? null,
                'payment_method' => $itemData['payment_method'] ?? null,
                'description' => $itemData['description'] ?? null,
                'receipt_path' => $receiptPath,
            ];
        }

        // Update the expense
        $expense->update([
            'category' => $validated['category'],
            'expense_date' => $validated['expense_date'],
            'description' => $validated['description'],
            'amount' => $totalAmount,
            'items' => $processedItems,
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
}
