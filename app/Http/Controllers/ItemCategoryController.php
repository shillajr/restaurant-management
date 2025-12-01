<?php

namespace App\Http\Controllers;

use App\Models\ItemCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ItemCategoryController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('categories', [
            'category_name' => ['required', 'string', 'max:255', 'unique:item_categories,name'],
            'category_status' => ['required', 'in:active,inactive'],
        ]);

        ItemCategory::create([
            'name' => trim($validated['category_name']),
            'status' => $validated['category_status'],
        ]);

        return redirect()
            ->route('settings', ['tab' => 'products', 'product_section' => 'items'])
            ->with('success', 'Category created successfully!')
            ->with('activeTab', 'products')
            ->with('productSection', 'items');
    }
}
