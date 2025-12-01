<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('vendors', [
            'vendor_name' => ['required', 'string', 'max:255', 'unique:vendors,name'],
            'vendor_email' => ['nullable', 'email', 'max:255'],
            'vendor_phone' => ['nullable', 'string', 'max:50'],
            'vendor_status' => ['required', 'in:active,inactive'],
        ]);

        Vendor::create([
            'name' => trim($validated['vendor_name']),
            'email' => $validated['vendor_email'] ? trim($validated['vendor_email']) : null,
            'phone' => $validated['vendor_phone'] ? trim($validated['vendor_phone']) : null,
            'is_active' => $validated['vendor_status'] === 'active',
        ]);

        return redirect()
            ->route('settings', ['tab' => 'products', 'product_section' => 'vendors'])
            ->with('success', 'Vendor created successfully!')
            ->with('activeTab', 'products')
            ->with('productSection', 'vendors');
    }

    public function update(Request $request, Vendor $vendor): RedirectResponse
    {
        $validated = $request->validateWithBag('vendors', [
            'vendor_name' => ['required', 'string', 'max:255', Rule::unique('vendors', 'name')->ignore($vendor->id)],
            'vendor_email' => ['nullable', 'email', 'max:255'],
            'vendor_phone' => ['nullable', 'string', 'max:50'],
            'vendor_status' => ['required', 'in:active,inactive'],
        ]);

        $vendor->update([
            'name' => trim($validated['vendor_name']),
            'email' => $validated['vendor_email'] ? trim($validated['vendor_email']) : null,
            'phone' => $validated['vendor_phone'] ? trim($validated['vendor_phone']) : null,
            'is_active' => $validated['vendor_status'] === 'active',
        ]);

        return redirect()
            ->route('settings', ['tab' => 'products', 'product_section' => 'vendors'])
            ->with('success', 'Vendor updated successfully.')
            ->with('activeTab', 'products')
            ->with('productSection', 'vendors');
    }

    public function archive(Vendor $vendor): RedirectResponse
    {
        if (! $vendor->is_active) {
            return redirect()
                ->route('settings', ['tab' => 'products', 'product_section' => 'vendors'])
                ->with('info', 'Vendor is already archived.')
                ->with('activeTab', 'products')
                ->with('productSection', 'vendors');
        }

        $vendor->update(['is_active' => false]);

        return redirect()
            ->route('settings', ['tab' => 'products', 'product_section' => 'vendors'])
            ->with('success', 'Vendor archived successfully.')
            ->with('activeTab', 'products')
            ->with('productSection', 'vendors');
    }

    public function restore(Vendor $vendor): RedirectResponse
    {
        if ($vendor->is_active) {
            return redirect()
                ->route('settings', ['tab' => 'products', 'product_section' => 'vendors'])
                ->with('info', 'Vendor is already active.')
                ->with('activeTab', 'products')
                ->with('productSection', 'vendors');
        }

        $vendor->update(['is_active' => true]);

        return redirect()
            ->route('settings', ['tab' => 'products', 'product_section' => 'vendors'])
            ->with('success', 'Vendor reactivated successfully.')
            ->with('activeTab', 'products')
            ->with('productSection', 'vendors');
    }
}
