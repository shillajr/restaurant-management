@extends('layouts.app')

@section('title', 'Add Expense')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-10" x-data="expenseForm()">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Add Expense</h1>
                    <p class="mt-2 text-sm text-gray-600">Record expense transactions with multiple items</p>
                </div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            <!-- Form -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <form action="{{ route('expenses.store') }}" method="POST" 
                        @submit="prepareSubmit"
                      class="p-6 space-y-6"
                      enctype="multipart/form-data">
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

                    <!-- Basic Information -->
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Expense Date -->
                        <div>
                            <label for="expense_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Expense Date <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   name="expense_date" 
                                   id="expense_date"
                                   x-model="expenseDate"
                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                   value="{{ date('Y-m-d') }}"
                                   required>
                        </div>
                    </div>

                    <!-- Hidden Category Field -->
                    <input type="hidden" name="category" value="general" x-model="category">

                    <!-- Expense Items Section -->
                    <div class="border-t border-gray-200 pt-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Expense Items</h3>
                            <button type="button" 
                                    @click="addItem"
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Item
                            </button>
                        </div>

                        <!-- Items List -->
                        <div class="space-y-6">
                            <template x-for="(item, index) in items" :key="index">
                                <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                    <!-- Item Header -->
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-medium text-gray-700" x-text="'Item #' + (index + 1)"></h4>
                                        <button type="button"
                                                @click="removeItem(index)"
                                                class="text-red-600 hover:text-red-900">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>

                                    <!-- Item Details Grid -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4">
                                        <!-- Item Selection -->
                                        <div>
                                            <label :for="'item_' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                                Item <span class="text-red-500">*</span>
                                            </label>
                                            <select x-model="item.item_id" 
                                                    @change="updateItemDetails(index)"
                                                    :name="'items[' + index + '][item_id]'"
                                                    :id="'item_' + index"
                                                    class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                    required>
                                                <option value="">Select item</option>
                                                <template x-for="availableItem in availableItems" :key="availableItem.id">
                                                    <option :value="availableItem.id" x-text="availableItem.name"></option>
                                                </template>
                                            </select>
                                        </div>

                                        <!-- Vendor (Auto-filled) -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Vendor
                                            </label>
                                            <input type="text" 
                                                   x-model="item.vendor" 
                                                   readonly
                                                   class="block w-full px-3 py-2 text-sm border border-gray-200 rounded-md bg-white text-gray-600"
                                                   placeholder="Auto-filled">
                                        </div>

                                        <!-- Quantity -->
                                        <div>
                                            <label :for="'quantity_' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                                Quantity <span class="text-red-500">*</span>
                                            </label>
                                            <input type="number" 
                                                   x-model.number="item.quantity"
                                                   @input="calculateLineTotal(index)"
                                                   :name="'items[' + index + '][quantity]'"
                                                   :id="'quantity_' + index"
                                                   min="1"
                                                   step="0.01"
                                                   class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                   required>
                                        </div>

                                        <!-- Unit Price -->
                                        <div>
                                            <label :for="'unit_price_' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                                Unit Price <span class="text-red-500">*</span>
                                            </label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                    <span class="text-gray-500 text-sm">$</span>
                                                </div>
                                                <input type="number" 
                                                       x-model.number="item.unit_price"
                                                       @input="calculateLineTotal(index)"
                                                       :name="'items[' + index + '][unit_price]'"
                                                       :id="'unit_price_' + index"
                                                       min="0.01"
                                                       step="0.01"
                                                       class="block w-full pl-7 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                       required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Line Total -->
                                    <div class="mb-4 pb-4 border-b border-gray-200">
                                        <div class="flex justify-between items-center">
                                            <span class="text-sm font-medium text-gray-700">Line Total:</span>
                                            <span class="text-lg font-bold text-gray-900">
                                                $<span x-text="item.line_total.toFixed(2)">0.00</span>
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Additional Details Section -->
                                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                                        <!-- Payment Reference -->
                                        <div>
                                            <label :for="'invoice_' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                                Payment Reference
                                            </label>
                                            <input type="text" 
                                                   x-model="item.invoice_number"
                                                   :name="'items[' + index + '][invoice_number]'"
                                                   :id="'invoice_' + index"
                                                   class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="REF-001">
                                        </div>

                                        <!-- Payment Method -->
                                        <div>
                                            <label :for="'payment_' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                                Payment Method
                                            </label>
                                            <select x-model="item.payment_method"
                                                    :name="'items[' + index + '][payment_method]'"
                                                    :id="'payment_' + index"
                                                    class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                                <option value="">Select method</option>
                                                <option value="cash">Cash</option>
                                                <option value="credit_card">Credit Card</option>
                                                <option value="debit_card">Debit Card</option>
                                                <option value="bank_transfer">Bank Transfer</option>
                                                <option value="check">Check</option>
                                                <option value="mobile_money">Mobile Money</option>
                                                <option value="other">Other</option>
                                            </select>
                                        </div>

                                        <!-- Receipt Upload -->
                                        <div>
                                            <label :for="'receipt_' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                                Receipt/Invoice
                                            </label>
                                            <div class="relative">
                                                <input type="file" 
                                                       :name="'items[' + index + '][receipt]'"
                                                       :id="'receipt_' + index"
                                                       @change="item.receipt_name = $event.target.files[0]?.name || ''"
                                                       accept="image/*,.pdf"
                                                       class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                            </div>
                                            <p x-show="item.receipt_name" x-text="item.receipt_name" class="mt-1 text-xs text-gray-500"></p>
                                        </div>
                                    </div>

                                    <!-- Description/Notes -->
                                    <div class="mt-4">
                                        <label :for="'description_' + index" class="block text-sm font-medium text-gray-700 mb-1">
                                            Additional Details/Notes
                                        </label>
                                        <textarea x-model="item.description"
                                                  :name="'items[' + index + '][description]'"
                                                  :id="'description_' + index"
                                                  rows="2"
                                                  class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                  placeholder="Add any additional details about this expense item..."></textarea>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty State -->
                            <div x-show="items.length === 0" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="mt-2 text-sm text-gray-500">No items added yet.</p>
                                <p class="text-xs text-gray-400">Click "Add Item" to start adding expense items.</p>
                            </div>
                        </div>

                        <!-- Grand Total -->
                        <div class="mt-6 pt-6 border-t border-gray-300">
                            <div class="flex justify-between items-center">
                                <span class="text-lg font-medium text-gray-900">Grand Total:</span>
                                <span class="text-2xl font-bold text-blue-600">
                                    $<span x-text="grandTotal.toFixed(2)">0.00</span>
                                </span>
                            </div>
                        </div>

                        <!-- Hidden input for total amount -->
                        <input type="hidden" name="amount" :value="grandTotal">
                    </div>

                    <!-- Additional Details -->
                    <div class="border-t border-gray-200 pt-6 space-y-6">
                        <h3 class="text-lg font-medium text-gray-900">General Notes</h3>

                        <!-- General Description -->
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                                General Description/Notes
                            </label>
                            <textarea name="description" 
                                      id="description" 
                                      rows="3"
                                      x-model="description"
                                      class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                      placeholder="Add any general notes about this expense transaction..."></textarea>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('dashboard') }}" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                :disabled="items.length === 0"
                                :class="{ 'opacity-50 cursor-not-allowed': items.length === 0 }">
                            Submit Expense
                        </button>
                    </div>
                </form>
            </div>
    </div>

@push('scripts')
<script>
        function expenseForm() {
            return {
                category: 'general',
                expenseDate: '{{ date("Y-m-d") }}',
                description: '',
                items: [],
                availableItems: {!! json_encode(\App\Models\Item::select('id', 'name', 'vendor', 'unit_price')->get()) !!},

                init() {
                    // Add one item by default
                    this.addItem();
                },

                addItem() {
                    this.items.push({
                        item_id: '',
                        vendor: '',
                        quantity: 1,
                        unit_price: 0,
                        line_total: 0,
                        invoice_number: '',
                        payment_method: '',
                        description: '',
                        receipt_name: ''
                    });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                },

                updateItemDetails(index) {
                    const selectedItemId = this.items[index].item_id;
                    const selectedItem = this.availableItems.find(item => item.id == selectedItemId);
                    
                    if (selectedItem) {
                        this.items[index].vendor = selectedItem.vendor || 'N/A';
                        this.items[index].unit_price = parseFloat(selectedItem.unit_price) || 0;
                        this.calculateLineTotal(index);
                    }
                },

                calculateLineTotal(index) {
                    const item = this.items[index];
                    item.line_total = (parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0);
                },

                get grandTotal() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.line_total) || 0), 0);
                },

                prepareSubmit(e) {
                    if (this.items.length === 0) {
                        e.preventDefault();
                        alert('Please add at least one item to the expense.');
                        return false;
                    }
                }
            }
        }
</script>
@endpush
@endsection
