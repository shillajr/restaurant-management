<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\ChefRequisition;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
        
        // Filter by workflow status
        if ($request->filled('workflow_status')) {
            $query->where('workflow_status', $request->workflow_status);
        }
        
        // Search by PO number or requisition ID
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('po_number', 'like', '%' . $request->search . '%')
                  ->orWhere('requisition_id', 'like', '%' . $request->search . '%');
            });
        }
        
        // Filter by date range
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $purchaseOrders = $query->paginate(15)->withQueryString();

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    /**
     * Show the form for creating a new resource.
     * Redirect to purchase orders index - POs are created from requisitions
     */
    public function create(Request $request)
    {
        return redirect()->route('purchase-orders.index')
            ->with('info', 'Purchase Orders are generated from approved requisitions. Please go to the requisition and click "Generate Purchase Order".');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('PurchaseOrderController@store - Request received', [
            'user_id' => auth()->id(),
            'request_data' => $request->all(),
            'session_id' => session()->getId(),
        ]);
        
        try {
            // Lean validation and creation from approved requisition
            $validated = $request->validate([
                'requisition_id' => 'required|exists:chef_requisitions,id',
                'requested_delivery_date' => 'nullable|date',
                'notes' => 'nullable|string',
            ]);

            $req = ChefRequisition::with('purchaseOrder')->findOrFail($validated['requisition_id']);
            
            // Verify requisition is approved
            if ($req->status !== 'approved') {
                return back()->with('error', 'Only approved requisitions can generate a Purchase Order.');
            }
            
            // Check if PO already exists
            if ($req->purchaseOrder) {
                return redirect()->route('purchase-orders.show', $req->purchaseOrder->id)
                    ->with('info', 'A Purchase Order already exists for this requisition.');
            }

            $items = collect($req->items ?? []);
            
            // Ensure there are items
            if ($items->isEmpty()) {
                return back()->with('error', 'Cannot create Purchase Order: No items found in requisition.');
            }
            
            $totalQuantity = $items->sum(function ($i) { return (float)($i['quantity'] ?? 0); });
            $subtotal = $items->sum(function ($i) { return (float)($i['quantity'] ?? 0) * (float)($i['price'] ?? 0); });
            $grandTotal = $subtotal;

            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePONumber(),
                'requisition_id' => $req->id,
                'created_by' => $req->chef_id,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'assigned_to' => Auth::id(),
                'requested_delivery_date' => $validated['requested_delivery_date'] ?? $req->requested_for_date,
                'items' => $items->map(function ($it) {
                    $qty = (float)($it['quantity'] ?? 0);
                    $price = (float)($it['price'] ?? 0);
                    return [
                        'item' => $it['item'] ?? ($it['item_id'] ?? ''),
                        'item_id' => $it['item_id'] ?? null,
                        'vendor' => $it['vendor'] ?? null,
                        'unit' => $it['uom'] ?? ($it['unit'] ?? ''),
                        'uom' => $it['uom'] ?? ($it['unit'] ?? ''),
                        'price' => $price,
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'line_total' => $qty * $price,
                    ];
                })->values()->toArray(),
                'total_quantity' => $totalQuantity,
                'subtotal' => $subtotal,
                'tax' => 0,
                'other_charges' => 0,
                'grand_total' => $grandTotal,
                'status' => 'open',
                'workflow_status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            activity()
                ->performedOn($po)
                ->causedBy(Auth::user())
                ->withProperties([
                    'po_number' => $po->po_number,
                    'requisition_id' => $req->id
                ])
                ->log('Purchase Order generated from approved requisition');

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json($po, 201);
            }

            return redirect()->route('purchase-orders.show', $po->id)
                ->with('success', "Purchase Order {$po->po_number} generated successfully from requisition.");
                
        } catch (\Exception $e) {
            \Log::error('Error generating Purchase Order: ' . $e->getMessage(), [
                'requisition_id' => $request->input('requisition_id'),
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->with('error', 'Failed to generate Purchase Order: ' . $e->getMessage());
        }
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
            'workflow_status' => 'required|in:pending,sent_to_vendor,returned,approved,rejected'
        ]);

        $oldWorkflowStatus = $purchaseOrder->workflow_status;
        $newWorkflowStatus = $request->workflow_status;

        $purchaseOrder->update([
            'workflow_status' => $newWorkflowStatus
        ]);

        // Log the workflow status change
        activity()
            ->performedOn($purchaseOrder)
            ->causedBy(auth()->user())
            ->withProperties([
                'old_workflow_status' => $oldWorkflowStatus,
                'new_workflow_status' => $newWorkflowStatus
            ])
            ->log('PO workflow status updated');
        
        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order workflow status updated successfully to ' . ucfirst(str_replace('_', ' ', $newWorkflowStatus)) . '.');
    }

    /**
     * Approve PO and send vendor-specific notifications.
     */
    public function approve(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        DB::transaction(function () use ($request, $purchaseOrder) {
            $purchaseOrder->update([
                'status' => 'open',
                'workflow_status' => 'approved',
            ]);

            // Send emails to each vendor with their section
            $itemsByVendor = $purchaseOrder->getItemsByVendor();
            foreach ($itemsByVendor as $vendorSection) {
                $vendorName = $vendorSection['vendor_name'];
                // Attempt to find vendor contact info by name
                $contact = \App\Models\Vendor::where('name', $vendorName)->first();
                if ($contact && $contact->email) {
                    $lines = [];
                    foreach ($vendorSection['items'] as $it) {
                        $lines[] = sprintf("- %s | Qty: %s %s | Unit: %0.2f | Line: %0.2f",
                            $it['item'] ?? ($it['item_id'] ?? 'Item'),
                            $it['quantity'] ?? 0,
                            $it['uom'] ?? ($it['unit'] ?? ''),
                            (float)($it['price'] ?? 0),
                            (float)($it['price'] ?? 0) * (float)($it['quantity'] ?? 0)
                        );
                    }
                    $body = implode("\n", [
                        "Dear {$vendorName},",
                        "",
                        "Please find your approved Purchase Order section:",
                        "PO Number: {$purchaseOrder->po_number}",
                        "Linked Requisition: {$purchaseOrder->requisition_id}",
                        "Approved Date: " . now()->toDateTimeString(),
                        "",
                        "Items:",
                        implode("\n", $lines),
                        "",
                        sprintf("Vendor Subtotal: %0.2f", (float)$vendorSection['vendor_subtotal']),
                        "",
                        "Kindly confirm and arrange delivery as per instructions.",
                        "Regards,",
                        Auth::user()->name,
                    ]);
                    Mail::raw($body, function ($message) use ($contact, $purchaseOrder, $vendorName) {
                        $message->to($contact->email, $vendorName)
                            ->subject('Purchase Order ' . $purchaseOrder->po_number . ' - Approved');
                    });
                }
            }

            // After sending vendor emails, mark as sent_to_vendor
            $purchaseOrder->update(['workflow_status' => 'sent_to_vendor']);

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->log('PO approved and vendor notifications sent');
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO approved and vendor notifications sent.');
    }

    /**
     * Reject PO with reason and revert requisition.
     */
    public function reject(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($purchaseOrder, $validated) {
            $purchaseOrder->update([
                'status' => 'open',
                'workflow_status' => 'rejected',
                'notes' => $validated['rejection_reason'],
            ]);

            if ($purchaseOrder->requisition) {
                $purchaseOrder->requisition->update(['status' => 'rejected']);
            }

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->withProperties(['rejection_reason' => $validated['rejection_reason']])
                ->log('PO rejected');
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO rejected and requisition updated.');
    }

    /**
     * Return PO for changes and revert to requisition stage.
     */
    public function returnForChanges(Request $request, PurchaseOrder $purchaseOrder)
    {
        $this->authorize('update', $purchaseOrder);

        $validated = $request->validate([
            'return_reason' => 'required|string|max:1000',
        ]);

        DB::transaction(function () use ($purchaseOrder, $validated) {
            $purchaseOrder->update([
                'workflow_status' => 'returned',
                'notes' => $validated['return_reason'],
            ]);

            if ($purchaseOrder->requisition) {
                $purchaseOrder->requisition->update(['status' => 'changes_requested']);
            }

            activity()
                ->performedOn($purchaseOrder)
                ->causedBy(Auth::user())
                ->withProperties(['return_reason' => $validated['return_reason']])
                ->log('PO returned for changes');
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'PO returned for changes; requisition updated.');
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
            'receipt' => 'nullable|file|mimes:jpeg,jpg,png,pdf|max:5120',
            'purchased_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($request, $purchaseOrder, $validated) {
            // Store receipt file if provided
            $receiptPath = $request->hasFile('receipt') ? $request->file('receipt')->store('receipts', 'public') : null;

            // Update purchase order
            $purchaseOrder->update([
                'supplier_id' => $validated['supplier_id'],
                'invoice_number' => $validated['invoice_number'],
                'total_amount' => $validated['total_amount'],
                'receipt_path' => $receiptPath,
                'purchased_at' => $validated['purchased_date'] ?? now(),
                'status' => 'received'
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

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'Purchase order marked as purchased',
                'purchase_order' => $purchaseOrder
            ], 200);
        }

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
