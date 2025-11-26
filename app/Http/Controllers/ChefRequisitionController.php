<?php

namespace App\Http\Controllers;

use App\Models\ChefRequisition;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChefRequisitionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        // Start with base query
        $query = ChefRequisition::query();
        
        // Role-based filtering
        if ($user->hasRole('chef')) {
            $query->where('chef_id', $user->id);
        }
        
        // Text search (search in note or chef name)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('note', 'like', "%{$search}%")
                  ->orWhereHas('chef', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Status filter
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Date range filter
        if ($request->filled('date_from')) {
            $query->whereDate('requested_for_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('requested_for_date', '<=', $request->date_to);
        }
        
        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDir = $request->get('sort_dir', 'desc');
        
        if (in_array($sortBy, ['created_at', 'requested_for_date', 'status'])) {
            $query->orderBy($sortBy, $sortDir);
        } else {
            $query->orderBy('created_at', 'desc');
        }
        
        // Load relationships and paginate
        $requisitions = $query->with(['chef', 'checker'])
            ->paginate(15)
            ->withQueryString();

        return view('chef-requisitions.index', compact('requisitions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('chef-requisitions.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'requested_for_date' => 'required|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.item' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'note' => 'nullable|string',
        ]);

        $requisition = ChefRequisition::create([
            'chef_id' => Auth::id(),
            'requested_for_date' => $validated['requested_for_date'],
            'items' => $validated['items'],
            'note' => $validated['note'] ?? null,
            'status' => 'pending'
        ]);

        activity()
            ->performedOn($requisition)
            ->causedBy(Auth::user())
            ->log('Chef requisition created');

        return redirect()->route('chef-requisitions.index')
            ->with('success', 'Requisition created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ChefRequisition $chefRequisition)
    {
        $chefRequisition->load(['chef', 'checker']);
        
        return view('chef-requisitions.show', compact('chefRequisition'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ChefRequisition $chefRequisition)
    {
        if ($chefRequisition->status !== 'pending') {
            return redirect()->route('chef-requisitions.index')
                ->with('error', 'Only pending requisitions can be edited.');
        }
        
        return view('chef-requisitions.edit', compact('chefRequisition'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ChefRequisition $chefRequisition)
    {
        if ($chefRequisition->status !== 'pending') {
            return redirect()->route('chef-requisitions.index')
                ->with('error', 'Only pending requisitions can be updated.');
        }

        $validated = $request->validate([
            'requested_for_date' => 'required|date|after:today',
            'items' => 'required|array|min:1',
            'items.*.item' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit' => 'required|string',
            'note' => 'nullable|string',
        ]);

        $chefRequisition->update([
            'requested_for_date' => $validated['requested_for_date'],
            'items' => $validated['items'],
            'note' => $validated['note'] ?? null,
        ]);

        activity()
            ->performedOn($chefRequisition)
            ->causedBy(Auth::user())
            ->log('Chef requisition updated');

        return redirect()->route('chef-requisitions.index')
            ->with('success', 'Requisition updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ChefRequisition $chefRequisition)
    {
        if ($chefRequisition->status !== 'pending') {
            return redirect()->route('chef-requisitions.index')
                ->with('error', 'Only pending requisitions can be deleted.');
        }

        activity()
            ->performedOn($chefRequisition)
            ->causedBy(Auth::user())
            ->log('Chef requisition deleted');

        $chefRequisition->delete();

        return redirect()->route('chef-requisitions.index')
            ->with('success', 'Requisition deleted successfully.');
    }

    /**
     * Approve the requisition and generate Purchase Order
     */
    public function approve(Request $request, ChefRequisition $chefRequisition)
    {
        if ($chefRequisition->status !== 'pending') {
            return back()->with('error', 'Only pending requisitions can be approved.');
        }

        $validated = $request->validate([
            'requested_delivery_date' => 'nullable|date|after:today',
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        try {
            // Update requisition status
            $chefRequisition->update([
                'status' => 'approved',
                'checker_id' => Auth::id(),
                'checked_at' => now(),
            ]);

            // Calculate totals
            $items = $chefRequisition->items;
            $totalQuantity = collect($items)->sum('quantity');
            $subtotal = collect($items)->sum(function($item) {
                return ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
            });
            $tax = 0; // Can be calculated based on business rules
            $otherCharges = 0;
            $grandTotal = $subtotal + $tax + $otherCharges;

            // Create Purchase Order
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePONumber(),
                'requisition_id' => $chefRequisition->id,
                'created_by' => $chefRequisition->chef_id,
                'approved_by' => Auth::id(),
                'approved_at' => now(),
                'requested_delivery_date' => $validated['requested_delivery_date'] ?? null,
                'items' => $items,
                'total_quantity' => $totalQuantity,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'other_charges' => $otherCharges,
                'grand_total' => $grandTotal,
                'status' => 'open',
                'notes' => $validated['approval_notes'] ?? null,
            ]);

            activity()
                ->performedOn($chefRequisition)
                ->causedBy(Auth::user())
                ->withProperties(['po_number' => $po->po_number, 'po_id' => $po->id])
                ->log('Chef requisition approved and PO created');

            DB::commit();

            return redirect()->route('purchase-orders.show', $po->id)
                ->with('success', "Requisition approved successfully. Purchase Order {$po->po_number} has been created.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve requisition: ' . $e->getMessage());
        }
    }

    /**
     * Reject the requisition
     */
    public function reject(Request $request, ChefRequisition $chefRequisition)
    {
        if ($chefRequisition->status !== 'pending') {
            return back()->with('error', 'Only pending requisitions can be rejected.');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $chefRequisition->update([
            'status' => 'rejected',
            'checker_id' => Auth::id(),
            'checked_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        activity()
            ->performedOn($chefRequisition)
            ->causedBy(Auth::user())
            ->withProperties(['rejection_reason' => $validated['rejection_reason']])
            ->log('Chef requisition rejected');

        return back()->with('success', 'Requisition rejected successfully.');
    }

    /**
     * Request changes to the requisition
     */
    public function requestChanges(Request $request, ChefRequisition $chefRequisition)
    {
        if ($chefRequisition->status !== 'pending') {
            return back()->with('error', 'Only pending requisitions can have changes requested.');
        }

        $validated = $request->validate([
            'change_request' => 'required|string|max:1000',
        ]);

        $chefRequisition->update([
            'status' => 'changes_requested',
            'checker_id' => Auth::id(),
            'checked_at' => now(),
            'change_request' => $validated['change_request'],
        ]);

        activity()
            ->performedOn($chefRequisition)
            ->causedBy(Auth::user())
            ->withProperties(['change_request' => $validated['change_request']])
            ->log('Changes requested for chef requisition');

        return back()->with('success', 'Change request sent successfully.');
    }
}
