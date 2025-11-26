<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Chef Requisition - Restaurant Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">Create Chef Requisition</h1>
                <p class="mt-2 text-sm text-gray-600">Submit your ingredient and supply requests</p>
            </div>

            <!-- Form -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <form action="{{ route('chef-requisitions.store') }}" method="POST" 
                      x-data="requisitionForm()"
                      @submit.prevent="submitForm"
                      class="p-6 space-y-6">
                    @csrf

                    <!-- Display Validation Errors -->
                    @if ($errors->any())
                        <div class="bg-red-50 border-l-4 border-red-400 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Requested For Date -->
                    <div>
                        <label for="requested_for_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Requested For Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               id="requested_for_date" 
                               name="requested_for_date"
                               value="{{ old('requested_for_date') }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Items Section -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">
                                Items <span class="text-red-500">*</span>
                            </label>
                            <span class="text-xs text-gray-500">At least one item required</span>
                        </div>

                        <!-- Items Table -->
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">Item</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">Vendor</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/8">Price (TZS)</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/8">Quantity</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12">UoM</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/8">Line Total</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(row, index) in items" :key="index">
                                        <tr :class="row.priceEdited ? 'bg-yellow-50' : ''">
                                            <!-- Item Dropdown -->
                                            <td class="px-4 py-3">
                                                <select :name="'items[' + index + '][item_id]'"
                                                        x-model="row.item_id"
                                                        @change="selectItem(index)"
                                                        required
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                    <option value="">Select item...</option>
                                                    <template x-for="category in Object.keys(groupedItems)" :key="category">
                                                        <optgroup :label="category">
                                                            <template x-for="item in groupedItems[category]" :key="item.id">
                                                                <option :value="item.id" x-text="item.name + ' (' + item.uom + ')'"></option>
                                                            </template>
                                                        </optgroup>
                                                    </template>
                                                </select>
                                                <span x-show="row.priceEdited" class="inline-flex items-center text-xs text-yellow-700 mt-1">
                                                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Price modified
                                                </span>
                                            </td>

                                            <!-- Vendor (Auto-filled, Read-only) -->
                                            <td class="px-4 py-3">
                                                <input type="text"
                                                       :value="row.vendor"
                                                       readonly
                                                       class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50 text-sm text-gray-600 cursor-not-allowed">
                                                <input type="hidden" :name="'items[' + index + '][vendor]'" :value="row.vendor">
                                            </td>

                                            <!-- Price (Editable with tracking) -->
                                            <td class="px-4 py-3">
                                                <div class="relative">
                                                    <input type="number"
                                                           :name="'items[' + index + '][price]'"
                                                           x-model="row.price"
                                                           @input="updateLineTotal(index)"
                                                           @change="trackPriceChange(index)"
                                                           step="0.01"
                                                           min="0"
                                                           required
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                                           :class="row.priceEdited ? 'border-yellow-400 ring-1 ring-yellow-400' : ''">
                                                    <span x-show="row.defaultPrice && row.price != row.defaultPrice" 
                                                          class="absolute -top-2 -right-2 bg-yellow-400 text-yellow-900 text-xs px-1.5 py-0.5 rounded-full font-medium">
                                                        !
                                                    </span>
                                                </div>
                                                <input type="hidden" :name="'items[' + index + '][default_price]'" :value="row.defaultPrice">
                                                <input type="hidden" :name="'items[' + index + '][price_edited]'" :value="row.priceEdited ? '1' : '0'">
                                                <span x-show="row.defaultPrice && row.price != row.defaultPrice" 
                                                      class="text-xs text-gray-500 mt-1 block">
                                                    Was: <span x-text="formatCurrency(row.defaultPrice)"></span>
                                                </span>
                                            </td>

                                            <!-- Quantity -->
                                            <td class="px-4 py-3">
                                                <input type="number"
                                                       :name="'items[' + index + '][quantity]'"
                                                       x-model="row.quantity"
                                                       @input="updateLineTotal(index)"
                                                       step="0.01"
                                                       min="0.01"
                                                       required
                                                       placeholder="0.00"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            </td>

                                            <!-- UoM (Auto-filled, Read-only) -->
                                            <td class="px-4 py-3">
                                                <input type="text"
                                                       :value="row.uom"
                                                       readonly
                                                       class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50 text-sm text-gray-600 cursor-not-allowed text-center">
                                                <input type="hidden" :name="'items[' + index + '][uom]'" :value="row.uom">
                                            </td>

                                            <!-- Line Total (Auto-calculated) -->
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-900 text-sm" x-text="formatCurrency(row.lineTotal)"></div>
                                            </td>

                                            <!-- Delete Button -->
                                            <td class="px-4 py-3 text-center">
                                                <button type="button"
                                                        @click="removeItem(index)"
                                                        x-show="items.length > 1"
                                                        class="p-2 text-red-600 hover:bg-red-50 rounded-md transition-colors"
                                                        title="Remove item">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Add Another Item Button -->
                        <div class="mt-4">
                            <button type="button"
                                    @click="addItem()"
                                    class="w-full md:w-auto px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-md hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors flex items-center justify-center gap-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Add Another Item
                            </button>
                        </div>
                    </div>

                    <!-- Summary Panel -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Requisition Summary</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Items:</span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="items.length"></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Total Quantity:</span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="totalQuantity.toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-sm text-gray-600">Modified Prices:</span>
                                    <span class="text-sm font-semibold" 
                                          :class="modifiedPricesCount > 0 ? 'text-yellow-600' : 'text-gray-900'" 
                                          x-text="modifiedPricesCount"></span>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Subtotal:</span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(subtotal)"></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">Tax/Charges:</span>
                                    <span class="text-sm font-semibold text-gray-900">TZS 0.00</span>
                                </div>
                                <div class="flex justify-between items-center py-3 bg-indigo-50 rounded-lg px-4 -mx-4">
                                    <span class="text-base font-bold text-gray-900">Grand Total:</span>
                                    <span class="text-lg font-bold text-indigo-600" x-text="formatCurrency(grandTotal)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Price Changes Summary -->
                        <div x-show="modifiedPricesCount > 0" class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="text-sm font-semibold text-yellow-800 mb-2 flex items-center">
                                <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                Price Changes Detected
                            </h4>
                            <div class="bg-yellow-50 rounded-md p-3 space-y-1">
                                <template x-for="(row, index) in items" :key="index">
                                    <div x-show="row.priceEdited" class="text-xs text-yellow-900">
                                        <span x-text="getItemName(row.item_id)"></span>: 
                                        <span class="font-medium" x-text="formatCurrency(row.defaultPrice)"></span> → 
                                        <span class="font-bold" x-text="formatCurrency(row.price)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700 mb-2">
                            Additional Notes
                        </label>
                        <textarea id="note" 
                                  name="note"
                                  rows="4"
                                  placeholder="Add any special instructions, quality requirements, or delivery instructions..."
                                  class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('note') }}</textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('chef-requisitions.index') }}" 
                           class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                                :disabled="items.length === 0 || !isFormValid()"
                                class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            Submit Requisition
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Card -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Tips for submitting requisitions</h3>
                        <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                            <li>Select items from the registered Item Master</li>
                            <li>Vendor and default price are automatically populated</li>
                            <li>You can edit prices if needed - changes will be tracked</li>
                            <li>Request items at least 2 days in advance</li>
                            <li>Review the summary before submitting</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function requisitionForm() {
            return {
                // Sample items from Item Master (in production, fetch from API)
                availableItems: [
                    { id: 1, name: 'Tomatoes', category: 'Vegetables', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 3500, stock: 45 },
                    { id: 2, name: 'Onions', category: 'Vegetables', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 2800, stock: 60 },
                    { id: 3, name: 'Carrots', category: 'Vegetables', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 3200, stock: 30 },
                    { id: 4, name: 'Bell Peppers', category: 'Vegetables', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 5500, stock: 18 },
                    { id: 5, name: 'Chicken Breast', category: 'Meat', uom: 'kg', vendor: 'Quality Meats Ltd', price: 12000, stock: 30 },
                    { id: 6, name: 'Beef Sirloin', category: 'Meat', uom: 'kg', vendor: 'Quality Meats Ltd', price: 18000, stock: 25 },
                    { id: 7, name: 'Pork Chops', category: 'Meat', uom: 'kg', vendor: 'Quality Meats Ltd', price: 14000, stock: 20 },
                    { id: 8, name: 'Fresh Salmon', category: 'Seafood', uom: 'kg', vendor: 'Ocean Fresh Suppliers', price: 25000, stock: 10 },
                    { id: 9, name: 'Prawns', category: 'Seafood', uom: 'kg', vendor: 'Ocean Fresh Suppliers', price: 28000, stock: 8 },
                    { id: 10, name: 'Fresh Milk', category: 'Dairy', uom: 'L', vendor: 'Dairy Delights Co', price: 3000, stock: 50 },
                    { id: 11, name: 'Butter', category: 'Dairy', uom: 'kg', vendor: 'Dairy Delights Co', price: 8000, stock: 12 },
                    { id: 12, name: 'Cheddar Cheese', category: 'Dairy', uom: 'kg', vendor: 'Dairy Delights Co', price: 15000, stock: 8 },
                    { id: 13, name: 'Rice (Basmati)', category: 'Grains', uom: 'kg', vendor: 'Grain Wholesalers', price: 4200, stock: 100 },
                    { id: 14, name: 'Pasta (Spaghetti)', category: 'Grains', uom: 'kg', vendor: 'Grain Wholesalers', price: 3500, stock: 80 },
                    { id: 15, name: 'Olive Oil', category: 'Cooking Oils', uom: 'L', vendor: 'Premium Foods Co', price: 8500, stock: 12 },
                    { id: 16, name: 'Vegetable Oil', category: 'Cooking Oils', uom: 'L', vendor: 'Premium Foods Co', price: 5000, stock: 20 },
                    { id: 17, name: 'Black Pepper', category: 'Spices', uom: 'g', vendor: 'Spice Market Ltd', price: 15000, stock: 500 },
                    { id: 18, name: 'Salt', category: 'Spices', uom: 'kg', vendor: 'Spice Market Ltd', price: 1500, stock: 50 },
                    { id: 19, name: 'Bananas', category: 'Fruits', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 2500, stock: 40 },
                    { id: 20, name: 'Apples', category: 'Fruits', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 4500, stock: 25 }
                ],
                items: [
                    { item_id: '', vendor: '', price: 0, defaultPrice: 0, quantity: 0, uom: '', lineTotal: 0, priceEdited: false, originalPrice: 0 }
                ],
                
                get groupedItems() {
                    const grouped = {};
                    this.availableItems.forEach(item => {
                        if (!grouped[item.category]) {
                            grouped[item.category] = [];
                        }
                        grouped[item.category].push(item);
                    });
                    return grouped;
                },
                
                get subtotal() {
                    return this.items.reduce((sum, item) => sum + (item.lineTotal || 0), 0);
                },
                
                get grandTotal() {
                    return this.subtotal; // Add taxes/charges here if needed
                },
                
                get totalQuantity() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.quantity) || 0), 0);
                },
                
                get modifiedPricesCount() {
                    return this.items.filter(item => item.priceEdited).length;
                },
                
                selectItem(index) {
                    const selectedId = this.items[index].item_id;
                    if (!selectedId) return;
                    
                    const item = this.availableItems.find(i => i.id == selectedId);
                    if (item) {
                        this.items[index].vendor = item.vendor;
                        this.items[index].price = item.price;
                        this.items[index].defaultPrice = item.price;
                        this.items[index].originalPrice = item.price;
                        this.items[index].uom = item.uom;
                        this.items[index].priceEdited = false;
                        this.updateLineTotal(index);
                    }
                },
                
                updateLineTotal(index) {
                    const row = this.items[index];
                    const price = parseFloat(row.price) || 0;
                    const quantity = parseFloat(row.quantity) || 0;
                    row.lineTotal = price * quantity;
                },
                
                trackPriceChange(index) {
                    const row = this.items[index];
                    if (row.defaultPrice && parseFloat(row.price) !== parseFloat(row.defaultPrice)) {
                        row.priceEdited = true;
                    } else {
                        row.priceEdited = false;
                    }
                },
                
                addItem() {
                    this.items.push({
                        item_id: '',
                        vendor: '',
                        price: 0,
                        defaultPrice: 0,
                        quantity: 0,
                        uom: '',
                        lineTotal: 0,
                        priceEdited: false,
                        originalPrice: 0
                    });
                },
                
                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    }
                },
                
                formatCurrency(amount) {
                    return 'TZS ' + (amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },
                
                getItemName(itemId) {
                    const item = this.availableItems.find(i => i.id == itemId);
                    return item ? item.name : '';
                },
                
                isFormValid() {
                    return this.items.every(item => 
                        item.item_id && 
                        item.price > 0 && 
                        item.quantity > 0
                    );
                },
                
                submitForm(event) {
                    if (!this.isFormValid()) {
                        alert('Please fill in all required fields for each item.');
                        return;
                    }
                    
                    // If there are modified prices, confirm with user
                    if (this.modifiedPricesCount > 0) {
                        if (!confirm(`You have modified ${this.modifiedPricesCount} price(s). Do you want to proceed?`)) {
                            return;
                        }
                    }
                    
                    // Submit the form
                    event.target.submit();
                }
            }
        }
    </script>
</body>
</html>
