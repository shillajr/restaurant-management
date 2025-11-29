<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Display a listing of items.
     */
    public function index()
    {
        return redirect()->route('settings', ['tab' => 'items']);
    }

    /**
     * Store a newly created item.
     */
    public function store(Request $request)
    {
        $validated = $request->validateWithBag('items', [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'uom' => 'required|string|max:50',
            'vendor' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['vendor'] = trim($validated['vendor']);
        $validated['category'] = trim($validated['category']);

        Vendor::firstOrCreate(
            ['name' => $validated['vendor']],
            ['is_active' => true]
        );

        ItemCategory::firstOrCreate(
            ['name' => $validated['category']],
            ['status' => 'active']
        );

        $item = Item::create($validated);

        return redirect()->route('settings', ['tab' => 'items'])
                ->with('success', 'Item created successfully!')
                ->with('activeTab', 'items');
    }

    /**
     * Update the specified item.
     */
    public function update(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $validated = $request->validateWithBag('items', [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'uom' => 'required|string|max:50',
            'vendor' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['vendor'] = trim($validated['vendor']);
        $validated['category'] = trim($validated['category']);

        Vendor::firstOrCreate(
            ['name' => $validated['vendor']],
            ['is_active' => true]
        );

        ItemCategory::firstOrCreate(
            ['name' => $validated['category']],
            ['status' => 'active']
        );

        // Log price change if price has changed
        if ($item->price != $validated['price']) {
            $item->logPriceChange(
                $item->price,
                $validated['price'],
                Auth::user()->name ?? 'System'
            );
        }

        $item->update($validated);

        return redirect()->route('settings', ['tab' => 'items'])
                ->with('success', 'Item updated successfully!')
                ->with('activeTab', 'items');
    }

    /**
     * Remove the specified item.
     */
    public function destroy($id)
    {
        $item = Item::findOrFail($id);
        
        // Check if item is used in any requisitions or purchase orders
        // This would need to be implemented based on your requisition/PO models
        
        $item->delete();

        return redirect()->route('settings', ['tab' => 'items'])
                ->with('success', 'Item deleted successfully!')
                ->with('activeTab', 'items');
    }

    /**
     * Get items for requisition dropdown (API endpoint).
     */
    public function getActiveItems(Request $request)
    {
        $query = Item::active();

        // Filter by category if provided
        if ($request->has('category')) {
            $query->byCategory($request->category);
        }

        // Search by name if provided
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $items = $query->orderBy('name')->get();

        return response()->json($items);
    }

    /**
     * Get low stock items.
     */
    public function getLowStockItems()
    {
        $items = Item::lowStock()
                    ->active()
                    ->orderBy('stock')
                    ->get();

        return response()->json($items);
    }
}
