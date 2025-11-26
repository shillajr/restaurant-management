<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\ChefRequisition;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = PurchaseOrder::with(['requisition.chef', 'approver', 'creator']);
        
        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Search by PO number
        if ($request->filled('search')) {
            $query->where('po_number', 'like', '%' . $request->search . '%');
        }
        
        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $purchaseOrders = $query->paginate(15);

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // Get all approved requisitions that don't have POs yet
        $approvedRequisitions = ChefRequisition::with('chef')
            ->where('status', 'approved')
            ->whereDoesntHave('purchaseOrder')
            ->orderBy('created_at', 'desc')
            ->get();
        
        // If a specific requisition is selected
        $selectedRequisition = null;
        if ($request->filled('requisition_id')) {
            $selectedRequisition = ChefRequisition::with('chef')
                ->find($request->requisition_id);
        }
        
        return view('purchase-orders.create', compact('approvedRequisitions', 'selectedRequisition'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', PurchaseOrder::class);

        $validated = $request->validate([
            'chef_requisition_id' => 'required|exists:chef_requisitions,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.item' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'estimated_total' => 'nullable|numeric|min:0',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $purchaseOrder = PurchaseOrder::create([
            'chef_requisition_id' => $validated['chef_requisition_id'],
            'supplier_id' => $validated['supplier_id'] ?? null,
            'items' => $validated['items'],
            'estimated_total' => $validated['estimated_total'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
            'created_by' => Auth::id(),
        ]);

        // Update requisition status
        $purchaseOrder->chefRequisition()->update(['status' => 'fulfilled']);

        activity()
            ->performedOn($purchaseOrder)
            ->causedBy(Auth::user())
            ->log('Purchase order created');

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load(['requisition.chef', 'approver', 'assignedTo']);
        
        // Get items grouped by vendor
        $itemsByVendor = $purchaseOrder->getItemsByVendor();
        $vendorStats = $purchaseOrder->getVendorStats();
        
        return view('purchase-orders.show', compact('purchaseOrder', 'itemsByVendor', 'vendorStats'));
    }
    
    /**
     * Update the status of the specified purchase order.
     */
    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $request->validate([
            'status' => 'required|in:open,ordered,partially_received,received,closed,cancelled'
        ]);
        
        $oldStatus = $purchaseOrder->status;
        $newStatus = $request->status;
        
        $purchaseOrder->update([
            'status' => $newStatus
        ]);
        
        // Log the status change
        activity()
            ->performedOn($purchaseOrder)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_status' => $oldStatus,
                'new_status' => $newStatus
            ])
            ->log('PO status updated');
        
        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order status updated successfully to ' . ucfirst(str_replace('_', ' ', $newStatus)) . '.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        
        if ($purchaseOrder->status === 'completed') {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Completed purchase orders cannot be edited.');
        }
        
        $suppliers = Supplier::where('is_active', true)->get();
        
        return view('purchase-orders.edit', compact('purchaseOrder', 'suppliers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);
        
        if ($purchaseOrder->status === 'completed') {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Completed purchase orders cannot be updated.');
        }

        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'items' => 'required|array|min:1',
            'items.*.item' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'items.*.estimated_price' => 'nullable|numeric|min:0',
            'estimated_total' => 'nullable|numeric|min:0',
            'delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $purchaseOrder->update([
            'supplier_id' => $validated['supplier_id'] ?? null,
            'items' => $validated['items'],
            'estimated_total' => $validated['estimated_total'] ?? null,
            'delivery_date' => $validated['delivery_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        activity()
            ->performedOn($purchaseOrder)
            ->causedBy(Auth::user())
            ->log('Purchase order updated');

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('delete', $purchaseOrder);
        
        if ($purchaseOrder->status === 'completed') {
            return redirect()->route('purchase-orders.index')
                ->with('error', 'Completed purchase orders cannot be deleted.');
        }

        DB::transaction(function () use ($purchaseOrder) {
            // Revert requisition status if needed
            if ($purchaseOrder->chefRequisition) {
                $purchaseOrder->chefRequisition()->update(['status' => 'approved']);
            }

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->log('Purchase order deleted');

            $purchaseOrder->delete();
        });

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Purchase order deleted successfully.');
    }

    /**
     * Mark the purchase order as purchased/completed.
     */
    public function markPurchased(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('markPurchased', $purchaseOrder);
        
        if ($purchaseOrder->status === 'completed') {
            return back()->with('error', 'This purchase order is already marked as completed.');
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'invoice_number' => 'required|string|max:100',
            'total_amount' => 'required|numeric|min:0',
            'receipt' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'purchased_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($request, $purchaseOrder, $validated) {
            // Store receipt file
            $receiptPath = $request->file('receipt')->store('receipts', 'public');

            // Update purchase order
            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'total_amount' => $validated['total_amount'],
                'receipt_path' => $receiptPath,
                'purchased_at' => $validated['purchased_date'] ?? now(),
                'status' => 'completed'
            ]);

            // Create expense record
            $purchaseOrder->expense()->create([
                'ledger_code' => 'PURCHASE',
                'amount' => $validated['total_amount'],
                'date' => $validated['purchased_date'] ?? now(),
                'description' => 'Purchase Order #' . $purchaseOrder->id . ' - Invoice: ' . $validated['invoice_number'],
                'receipt_url' => $receiptPath,
                'approved_by' => Auth::id(),
                'approved_at' => now()
            ]);

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->withProperties([
                    'invoice_number' => $validated['invoice_number'],
                    'total_amount' => $validated['total_amount'],
                ])
                ->log('Purchase order marked as purchased');
        });

        return back()->with('success', 'Purchase marked as completed and expense recorded.');
    }

    /**
     * Download the receipt/invoice file.
     */
    public function downloadReceipt(PurchaseOrder $purchaseOrder)
    {
        $this->authorize('view', $purchaseOrder);
        
        if (!$purchaseOrder->receipt_path || !Storage::disk('public')->exists($purchaseOrder->receipt_path)) {
            return back()->with('error', 'Receipt file not found.');
        }

        return Storage::disk('public')->download($purchaseOrder->receipt_path);
    }
}
