<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemController extends Controller
{
    /**
     * Display a listing of items.
     */
    public function index(Request $request)
    {
        $items = Item::where('is_seeded', false)->orderBy('name')->get();
        $vendors = Vendor::orderBy('name')->get();
        $itemCategories = ItemCategory::orderBy('name')->get();

        $editingItem = null;
        if ($request->filled('edit_item')) {
            $editingItem = $items->firstWhere('id', (int) $request->query('edit_item'));
        }

        $unitOptions = [
            'piece',
            'kg',
            'liters',
            'pack',
            'box',
            'tray',
            'crate',
        ];

        return view('items.index', [
            'items' => $items,
            'vendors' => $vendors,
            'itemCategories' => $itemCategories,
            'editingItem' => $editingItem,
            'unitOptions' => $unitOptions,
        ]);
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
            'vendor_id' => ['required', 'exists:vendors,id'],
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $vendor = Vendor::findOrFail($validated['vendor_id']);
        $validated['category'] = trim($validated['category']);

        ItemCategory::firstOrCreate(
            ['name' => $validated['category']],
            ['status' => 'active']
        );

        $item = Item::create([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'uom' => $validated['uom'],
            'vendor' => $vendor->name,
            'price' => $validated['price'],
            'status' => $validated['status'],
            'stock' => $validated['stock'] ?? null,
            'reorder_level' => $validated['reorder_level'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_seeded' => false,
        ]);

        return $this->redirectAfterItemAction($request, 'Item created successfully!');
    }

    /**
     * Update the specified item.
     */
    public function update(Request $request, $id)
    {
        $item = Item::where('is_seeded', false)->findOrFail($id);

        $validated = $request->validateWithBag('items', [
            'name' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'uom' => 'required|string|max:50',
            'vendor_id' => ['required', 'exists:vendors,id'],
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
            'stock' => 'nullable|numeric|min:0',
            'reorder_level' => 'nullable|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $vendor = Vendor::findOrFail($validated['vendor_id']);
        $validated['category'] = trim($validated['category']);

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

        $item->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'uom' => $validated['uom'],
            'vendor' => $vendor->name,
            'price' => $validated['price'],
            'status' => $validated['status'],
            'stock' => $validated['stock'] ?? null,
            'reorder_level' => $validated['reorder_level'] ?? null,
            'description' => $validated['description'] ?? null,
            'is_seeded' => false,
        ]);

        return $this->redirectAfterItemAction($request, 'Item updated successfully!');
    }

    /**
     * Remove the specified item.
     */
    public function destroy(Request $request, $id)
    {
        $item = Item::where('is_seeded', false)->findOrFail($id);
        
        // Check if item is used in any requisitions or purchase orders
        // This would need to be implemented based on your requisition/PO models
        
        $item->delete();

        return $this->redirectAfterItemAction($request, 'Item deleted successfully!');
    }

    /**
     * Get items for requisition dropdown (API endpoint).
     */
    public function getActiveItems(Request $request)
    {
        $query = Item::active()->where('is_seeded', false);

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
                ->where('is_seeded', false)
                    ->active()
                    ->orderBy('stock')
                    ->get();

        return response()->json($items);
    }

    protected function redirectAfterItemAction(Request $request, string $message): RedirectResponse
    {
        if ($request->input('return_to') === 'items') {
            return redirect()
                ->route('items.index')
                ->with('success', $message);
        }

        return redirect()
            ->route('settings', ['tab' => 'products', 'product_section' => 'items'])
            ->with('success', $message)
            ->with('activeTab', 'products')
            ->with('productSection', 'items');
    }
}
