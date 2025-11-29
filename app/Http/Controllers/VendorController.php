<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('vendors', [
            'vendor_name' => ['required', 'string', 'max:255', 'unique:vendors,name'],
            'vendor_phone' => ['nullable', 'string', 'max:50'],
            'vendor_status' => ['required', 'in:active,inactive'],
        ]);

        Vendor::create([
            'name' => trim($validated['vendor_name']),
            'phone' => $validated['vendor_phone'] ? trim($validated['vendor_phone']) : null,
            'is_active' => $validated['vendor_status'] === 'active',
        ]);

        return redirect()
            ->route('settings', ['tab' => 'items'])
            ->with('success', 'Vendor created successfully!')
            ->with('activeTab', 'items');
    }
}
