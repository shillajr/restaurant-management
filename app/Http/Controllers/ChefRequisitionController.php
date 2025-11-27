<?php

namespace App\Http\Controllers;

use App\Models\ChefRequisition;
use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Auth;

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

        if (str_starts_with($request->path(), 'api')) {
            return response()->json([
                'data' => $requisitions->items(),
                'current_page' => $requisitions->currentPage(),
                'last_page' => $requisitions->lastPage(),
                'per_page' => $requisitions->perPage(),
                'total' => $requisitions->total(),
            ]);
        }

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
            'items.*.item_id' => 'required|integer|exists:items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.uom' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'note' => 'nullable|string',
        ]);

        // Normalize items to expected internal structure (add item name & unit alias)
        $normalizedItems = collect($validated['items'])->map(function ($row) {
            $itemModel = Item::find($row['item_id']);
            return [
                'item_id' => $row['item_id'],
                'item' => $itemModel->name ?? ($row['item_id'] ?? 'Unknown'),
                'vendor' => $row['vendor'] ?? ($itemModel->vendor ?? null),
                'quantity' => (float)$row['quantity'],
                'unit' => $row['uom'] ?? ($itemModel->uom ?? null),
                'uom' => $row['uom'] ?? ($itemModel->uom ?? null),
                'price' => (float)$row['price'],
                'defaultPrice' => isset($row['default_price']) ? (float)$row['default_price'] : (float)($itemModel->price ?? $row['price']),
                'priceEdited' => isset($row['price_edited']) ? ($row['price_edited'] === '1') : false,
                'originalPrice' => isset($row['originalPrice']) ? (float)$row['originalPrice'] : (float)($itemModel->price ?? $row['price']),
            ];
        })->toArray();

        $requisition = ChefRequisition::create([
            'chef_id' => Auth::id(),
            'requested_for_date' => $validated['requested_for_date'],
            'items' => $normalizedItems,
            'note' => $validated['note'] ?? null,
            'status' => 'pending'
        ]);

        activity()
            ->performedOn($requisition)
            ->causedBy(Auth::user())
            ->log('Chef requisition created');

        if (str_starts_with($request->path(), 'api')) {
            return response()->json($requisition->load('chef'), 201);
        }

        return redirect()->route('chef-requisitions.index')
            ->with('success', 'Requisition created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, ChefRequisition $chefRequisition)
    {
        $chefRequisition->load(['chef', 'checker', 'purchaseOrder']);
        
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($chefRequisition);
        }

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
    public function destroy(Request $request, ChefRequisition $chefRequisition)
    {
        // Status check takes precedence: only pending can be deleted
        if ($chefRequisition->status !== 'pending') {
            if (str_starts_with($request->path(), 'api')) {
                return response()->json([
                    'message' => 'Only pending requisitions can be deleted.'
                ], 422);
            }
            return redirect()->route('chef-requisitions.index')
                ->with('error', 'Only pending requisitions can be deleted.');
        }

        // Ownership check: only owner can delete pending requisitions
        $isOwner = Auth::id() === $chefRequisition->chef_id;
        if (!$isOwner) {
            if (str_starts_with($request->path(), 'api')) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            return redirect()->route('chef-requisitions.index')->with('error', 'Forbidden');
        }

        activity()
            ->performedOn($chefRequisition)
            ->causedBy(Auth::user())
            ->log('Chef requisition deleted');

        $chefRequisition->delete();

        if (str_starts_with($request->path(), 'api')) {
            return response()->json(['message' => 'Requisition deleted successfully.'], 200);
        }

        return redirect()->route('chef-requisitions.index')
            ->with('success', 'Requisition deleted successfully.');
    }

    /**
     * Approve the requisition
     */
    public function approve(Request $request, ChefRequisition $chefRequisition)
    {
        if ($chefRequisition->status !== 'pending') {
            if (str_starts_with($request->path(), 'api')) {
                return response()->json(['message' => 'Only pending requisitions can be approved.'], 422);
            }
            return back()->with('error', 'Only pending requisitions can be approved.');
        }

        $validated = $request->validate([
            'approval_notes' => 'nullable|string|max:1000',
        ]);

        try {
            // Update requisition status
            $chefRequisition->update([
                'status' => 'approved',
                'checker_id' => Auth::id(),
                'checked_at' => now(),
            ]);

            activity()
                ->performedOn($chefRequisition)
                ->causedBy(Auth::user())
                ->log('Chef requisition approved');

            if (str_starts_with($request->path(), 'api')) {
                return response()->json([
                    'message' => 'Requisition approved successfully',
                    'requisition' => $chefRequisition
                ], 200);
            }

            return redirect()->route('chef-requisitions.show', $chefRequisition->id)
                ->with('success', "Requisition approved successfully.");

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to approve requisition: ' . $e->getMessage());
        }
    }

    /**
     * Reject the requisition
     */
    public function reject(Request $request, ChefRequisition $chefRequisition)
    {
        if ($chefRequisition->status !== 'pending') {
            if (str_starts_with($request->path(), 'api')) {
                return response()->json(['message' => 'Only pending requisitions can be rejected.'], 422);
            }
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

        if (str_starts_with($request->path(), 'api')) {
            return response()->json([
                'message' => 'Requisition rejected',
                'requisition' => $chefRequisition
            ], 200);
        }

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
