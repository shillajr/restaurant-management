@extends('layouts.app')

@section('title', 'Items')

@php
    use Illuminate\Support\Str;

    $activeVendors = $vendors->where('is_active', true)->values();
    $activeCategories = $itemCategories->where('status', 'active')->values();
    $categoryStats = [
        'total' => $itemCategories->count(),
        'active' => $activeCategories->count(),
        'inactive' => $itemCategories->where('status', 'inactive')->count(),
    ];
    $isEditingItem = filled($editingItem);
    $itemFormAction = $isEditingItem ? route('items.update', $editingItem) : route('items.store');
    $itemModalShouldOpen = $isEditingItem || $errors->items->any();
    $itemsInactiveCount = $items->where('status', 'inactive')->count();

    $selectedVendorId = old('vendor_id');
    if (! $selectedVendorId && $isEditingItem) {
        $matchedVendor = $vendors->firstWhere('name', $editingItem->vendor);
        $selectedVendorId = $matchedVendor?->id;
    }
@endphp

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Item catalog</h1>
                <p class="mt-2 text-sm text-gray-600">Everything your team can purchase, with pricing, vendor assignments, and stock visibility.</p>
            </div>
            <a href="{{ route('settings', ['tab' => 'products', 'product_section' => 'items']) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to settings
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div
            x-data="{
                showItemModal: @js($itemModalShouldOpen),
                open(which) { this[which] = true },
                close(which) { this[which] = false }
            }"
            class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200"
        >
            <div class="border-b border-gray-200 px-6 py-5">
                <h2 class="text-xl font-semibold text-gray-900">Items</h2>
                <p class="mt-1 text-sm text-gray-500">Maintain the catalog powering requisitions, purchase orders, and inventory monitoring.</p>
            </div>

            <div class="space-y-6 p-6">
                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total items</p>
                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $items->count() }}</p>
                    </div>
                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-green-700">Active vendors</p>
                        <p class="mt-2 text-2xl font-semibold text-green-800">{{ $activeVendors->count() }}</p>
                    </div>
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Inactive items</p>
                        <p class="mt-2 text-2xl font-semibold text-amber-800">{{ $itemsInactiveCount }}</p>
                    </div>
                </div>

                @if ($errors->items->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <ul class="list-disc space-y-1 pl-5">
                            @foreach ($errors->items->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 bg-gray-50 px-6 py-4">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                                <span>{{ $items->count() }} items</span>
                                <span aria-hidden="true">•</span>
                                <span>{{ $activeVendors->count() }} active vendors</span>
                                @if($itemCategories->count() > 0)
                                    <span aria-hidden="true">•</span>
                                    <span>{{ $categoryStats['active'] }} active categories</span>
                                @endif
                                @if($itemsInactiveCount > 0)
                                    <span aria-hidden="true">•</span>
                                    <span>{{ $itemsInactiveCount }} inactive</span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-500">Categories can be managed from Settings → Products → Categories.</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" @if($activeVendors->isNotEmpty()) @click.prevent="open('showItemModal')" @endif @if($activeVendors->isEmpty()) disabled @endif class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 {{ $activeVendors->isEmpty() ? 'cursor-not-allowed opacity-60 hover:bg-blue-600' : '' }}">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                </svg>
                                {{ $isEditingItem ? 'Update item' : 'Add new item' }}
                            </button>
                        </div>
                        @if($activeVendors->isEmpty())
                            <p class="w-full text-right text-xs font-medium text-red-600">Add at least one active vendor in Settings → Products → Vendors before creating new items.</p>
                        @endif
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                <tr>
                                    <th class="px-6 py-3 text-left">Item</th>
                                    <th class="px-6 py-3 text-left">Vendor</th>
                                    <th class="px-6 py-3 text-left">Price</th>
                                    <th class="px-6 py-3 text-left">Stock</th>
                                    <th class="px-6 py-3 text-left">Status</th>
                                    <th class="px-6 py-3 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($items as $item)
                                    @php
                                        $isLowStock = !is_null($item->reorder_level) && !is_null($item->stock) && $item->stock <= $item->reorder_level;
                                        $isInactive = $item->status !== 'active';
                                    @endphp
                                    <tr class="{{ $isEditingItem && $editingItem && $editingItem->id === $item->id ? 'bg-blue-50/50' : '' }}">
                                        <td class="px-6 py-4 align-top">
                                            <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                            <div class="mt-1 text-xs uppercase tracking-wide text-gray-500">{{ $item->category ?? 'Uncategorized' }} &middot; {{ $item->uom }}</div>
                                        </td>
                                        <td class="px-6 py-4 align-top text-gray-600">{{ $item->vendor }}</td>
                                        <td class="px-6 py-4 align-top font-semibold text-gray-900">{{ currency_format($item->price) }}</td>
                                        <td class="px-6 py-4 align-top text-gray-700">
                                            @if(!is_null($item->stock))
                                                <span class="{{ $isLowStock ? 'font-semibold text-red-600' : '' }}">{{ rtrim(rtrim(number_format((float) $item->stock, 2), '0'), '.') }}</span>
                                                @if($isLowStock)
                                                    <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">Low</span>
                                                @endif
                                            @else
                                                <span class="text-gray-400">—</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $isInactive ? 'bg-gray-200 text-gray-700' : 'bg-green-100 text-green-700' }}">
                                                {{ Str::ucfirst($item->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 align-top">
                                            <div class="flex items-center justify-end gap-3 text-sm">
                                                <a href="{{ route('items.index', ['edit_item' => $item->id]) }}" class="font-medium text-blue-600 hover:text-blue-700">Edit</a>
                                                <form action="{{ route('items.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Delete {{ $item->name }}? This cannot be undone.');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <input type="hidden" name="return_to" value="items">
                                                    <button type="submit" class="font-medium text-red-600 hover:text-red-700">Delete</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No items yet. Add your first item to get started.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700">
                    Need new categories? Add them from Settings → Products → Categories to keep this catalog organised.
                </div>
            </div>

            <div
                x-cloak
                x-show="showItemModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @keydown.escape.window="close('showItemModal')"
                class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4 py-6"
                @click.self="close('showItemModal')"
            >
                <div class="w-full max-w-3xl overflow-hidden rounded-lg bg-white shadow-2xl">
                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">{{ $isEditingItem ? 'Edit item' : 'Add new item' }}</h3>
                            <p class="mt-1 text-sm text-gray-500">Fields with <span class="text-red-500">*</span> are required.</p>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600" @click="close('showItemModal')">
                            <span class="sr-only">Close</span>
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                    <div class="max-h-[80vh] overflow-y-auto px-6 py-6">
                        @if ($errors->items->any())
                            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <ul class="list-disc space-y-1 pl-5">
                                    @foreach ($errors->items->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ $itemFormAction }}" method="POST" class="space-y-4">
                            @csrf
                            <input type="hidden" name="return_to" value="items">
                            @if ($isEditingItem)
                                @method('PUT')
                            @endif

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-2">
                                    <label for="item-name" class="block text-sm font-medium text-gray-700">Item name <span class="text-red-500">*</span></label>
                                    <input id="item-name" name="name" type="text" value="{{ old('name', $editingItem->name ?? '') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="item-category" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                                    <input id="item-category" name="category" list="item-categories" value="{{ old('category', $editingItem->category ?? '') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    <datalist id="item-categories">
                                        @foreach ($itemCategories->where('status', 'active') as $category)
                                            <option value="{{ $category->name }}"></option>
                                        @endforeach
                                    </datalist>
                                </div>
                                <div>
                                    <label for="item-uom" class="block text-sm font-medium text-gray-700">Unit of measure <span class="text-red-500">*</span></label>
                                    @php
                                        $selectedUom = old('uom', $editingItem->uom ?? '');
                                        $normalizedUnits = collect($unitOptions)->map(fn ($u) => Str::lower($u));
                                    @endphp
                                    <select id="item-uom" name="uom" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        <option value="" disabled @selected($selectedUom === '')>Select unit</option>
                                        @foreach ($unitOptions as $unit)
                                            <option value="{{ $unit }}" @selected(Str::lower($selectedUom) === Str::lower($unit))>{{ Str::upper($unit) }}</option>
                                        @endforeach
                                        @if ($selectedUom !== '' && ! $normalizedUnits->contains(Str::lower($selectedUom)))
                                            <option value="{{ $selectedUom }}" selected>{{ Str::upper($selectedUom) }}</option>
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label for="item-vendor" class="block text-sm font-medium text-gray-700">Vendor/Supplier <span class="text-red-500">*</span></label>
                                    @php
                                        $vendorOptions = $vendors->sortBy('name')->values();
                                        $currentVendorId = old('vendor_id', $selectedVendorId);
                                    @endphp
                                    <select id="item-vendor" name="vendor_id" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        <option value="" disabled @selected(empty($currentVendorId))>Select vendor</option>
                                        @foreach ($vendorOptions as $vendorOption)
                                            <option value="{{ $vendorOption->id }}" @selected((string) $currentVendorId === (string) $vendorOption->id)>
                                                {{ $vendorOption->name }}{{ ! $vendorOption->is_active ? ' (inactive)' : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">Manage vendors from Settings → Products → Vendors.</p>
                                </div>
                                <div>
                                    <label for="item-price" class="block text-sm font-medium text-gray-700">Price ({{ currency_label() }}) <span class="text-red-500">*</span></label>
                                    <input id="item-price" name="price" type="number" min="0" step="0.01" value="{{ old('price', $editingItem->price ?? '') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="item-status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                    <select id="item-status" name="status" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        <option value="active" @selected(old('status', $editingItem->status ?? 'active') === 'active')>Active</option>
                                        <option value="inactive" @selected(old('status', $editingItem->status ?? 'active') === 'inactive')>Inactive</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="item-stock" class="block text-sm font-medium text-gray-700">Current stock</label>
                                    <input id="item-stock" name="stock" type="number" min="0" step="0.01" value="{{ old('stock', $editingItem->stock ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label for="item-reorder" class="block text-sm font-medium text-gray-700">Reorder level</label>
                                    <input id="item-reorder" name="reorder_level" type="number" min="0" step="0.01" value="{{ old('reorder_level', $editingItem->reorder_level ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                </div>
                                <div class="sm:col-span-2">
                                    <label for="item-description" class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea id="item-description" name="description" rows="3" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('description', $editingItem->description ?? '') }}</textarea>
                                </div>
                            </div>

                            <div class="rounded-md bg-blue-50 px-3 py-3 text-xs text-blue-700">
                                Keep vendor and pricing details current so requisitions always reflect accurate costs.
                            </div>

                            <div class="flex items-center justify-end gap-3">
                                <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-700" @click="close('showItemModal')">Cancel</button>
                                <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                    {{ $isEditingItem ? 'Save item' : 'Create item' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush
