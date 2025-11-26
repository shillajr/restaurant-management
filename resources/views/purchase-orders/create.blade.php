<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Purchase Order - Restaurant Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Create Purchase Order</h1>
                    <p class="mt-2 text-sm text-gray-600">Generate a new purchase order for approved requisitions</p>
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
                <form action="{{ route('purchase-orders.store') }}" method="POST" 
                      x-data="purchaseOrderForm()"
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

                    <!-- Requisition Selection -->
                    <div>
                        <label for="requisition_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Link to Requisition (Optional)
                        </label>
                        <select name="requisition_id" id="requisition_id" 
                                class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Create standalone purchase order</option>
                            <option value="1">REQ-001 - Kitchen Supplies (Approved)</option>
                            <option value="2">REQ-002 - Fresh Produce (Approved)</option>
                            <option value="3">REQ-003 - Meat & Seafood (Approved)</option>
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Select an approved requisition to auto-populate items, or create a standalone order</p>
                        @error('requisition_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Supplier Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Supplier Information</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Supplier Selection -->
                            <div>
                                <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Supplier <span class="text-red-500">*</span>
                                </label>
                                <select name="supplier_id" id="supplier_id" 
                                        class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        required>
                                    <option value="">Select a supplier</option>
                                    <option value="1">ABC Food Distributors</option>
                                    <option value="2">Fresh Farm Produce</option>
                                    <option value="3">Ocean Seafood Supply</option>
                                    <option value="4">Metro Restaurant Supply</option>
                                    <option value="5">Quality Meat Co.</option>
                                </select>
                                @error('supplier_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Assigned To -->
                            <div>
                                <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-2">
                                    Assign to Purchaser <span class="text-red-500">*</span>
                                </label>
                                <select name="assigned_to" id="assigned_to" 
                                        class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        required>
                                    <option value="">Select purchaser</option>
                                    <option value="1">John Purchaser</option>
                                    <option value="2">Sarah Buyer</option>
                                </select>
                                @error('assigned_to')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Order Items -->
                    <div x-data="{ 
                        items: [{ id: 1, description: '', quantity: '', unit: '', unit_price: '', total: 0 }],
                        addItem() {
                            this.items.push({ 
                                id: this.items.length + 1, 
                                description: '', 
                                quantity: '', 
                                unit: '', 
                                unit_price: '', 
                                total: 0 
                            });
                        },
                        removeItem(index) {
                            if (this.items.length > 1) {
                                this.items.splice(index, 1);
                            }
                        },
                        calculateTotal(item) {
                            const qty = parseFloat(item.quantity) || 0;
                            const price = parseFloat(item.unit_price) || 0;
                            item.total = (qty * price).toFixed(2);
                            return item.total;
                        },
                        getGrandTotal() {
                            return this.items.reduce((sum, item) => {
                                return sum + (parseFloat(item.total) || 0);
                            }, 0).toFixed(2);
                        }
                    }">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">
                                Order Items <span class="text-red-500">*</span>
                            </label>
                            <button type="button" 
                                    @click="addItem()"
                                    class="inline-flex items-center px-3 py-1 border border-transparent text-sm font-medium rounded-md text-blue-700 bg-blue-100 hover:bg-blue-200">
                                <svg class="mr-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Add Item
                            </button>
                        </div>

                        <div class="border border-gray-300 rounded-md overflow-hidden">
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">Quantity</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">Unit</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Unit Price</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">Total</th>
                                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <template x-for="(item, index) in items" :key="item.id">
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <input type="text" 
                                                           :name="'items[' + index + '][description]'" 
                                                           x-model="item.description"
                                                           class="block w-full px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                           placeholder="Item description"
                                                           required>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <input type="number" 
                                                           :name="'items[' + index + '][quantity]'" 
                                                           x-model="item.quantity"
                                                           @input="calculateTotal(item)"
                                                           step="0.01"
                                                           min="0"
                                                           class="block w-full px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                           placeholder="0"
                                                           required>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <select :name="'items[' + index + '][unit]'" 
                                                            x-model="item.unit"
                                                            class="block w-full px-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                            required>
                                                        <option value="">Unit</option>
                                                        <option value="kg">kg</option>
                                                        <option value="lbs">lbs</option>
                                                        <option value="g">g</option>
                                                        <option value="oz">oz</option>
                                                        <option value="L">L</option>
                                                        <option value="ml">ml</option>
                                                        <option value="pcs">pcs</option>
                                                        <option value="box">box</option>
                                                        <option value="case">case</option>
                                                    </select>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="relative">
                                                        <div class="absolute inset-y-0 left-0 pl-2 flex items-center pointer-events-none">
                                                            <span class="text-gray-500 text-sm">$</span>
                                                        </div>
                                                        <input type="number" 
                                                               :name="'items[' + index + '][unit_price]'" 
                                                               x-model="item.unit_price"
                                                               @input="calculateTotal(item)"
                                                               step="0.01"
                                                               min="0"
                                                               class="block w-full pl-6 pr-2 py-1 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                               placeholder="0.00"
                                                               required>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div class="text-sm font-medium text-gray-900">
                                                        $<span x-text="calculateTotal(item)">0.00</span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <button type="button" 
                                                            @click="removeItem(index)"
                                                            x-show="items.length > 1"
                                                            class="text-red-600 hover:text-red-900">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                        </svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="bg-gray-50">
                                        <tr>
                                            <td colspan="4" class="px-4 py-3 text-right text-sm font-medium text-gray-900">
                                                Grand Total:
                                            </td>
                                            <td class="px-4 py-3 text-sm font-bold text-gray-900">
                                                $<span x-text="getGrandTotal()">0.00</span>
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Expected Delivery Date -->
                        <div>
                            <label for="expected_delivery" class="block text-sm font-medium text-gray-700 mb-2">
                                Expected Delivery Date
                            </label>
                            <input type="date" 
                                   name="expected_delivery" 
                                   id="expected_delivery" 
                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('expected_delivery')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Payment Terms -->
                        <div>
                            <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-2">
                                Payment Terms
                            </label>
                            <select name="payment_terms" id="payment_terms" 
                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select payment terms</option>
                                <option value="net_7">Net 7 days</option>
                                <option value="net_15">Net 15 days</option>
                                <option value="net_30">Net 30 days</option>
                                <option value="net_60">Net 60 days</option>
                                <option value="cod">Cash on Delivery</option>
                                <option value="advance">Advance Payment</option>
                            </select>
                            @error('payment_terms')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Delivery Instructions -->
                    <div>
                        <label for="delivery_instructions" class="block text-sm font-medium text-gray-700 mb-2">
                            Delivery Instructions
                        </label>
                        <textarea name="delivery_instructions" 
                                  id="delivery_instructions" 
                                  rows="3" 
                                  class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter any special delivery instructions..."></textarea>
                        @error('delivery_instructions')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Internal Notes
                        </label>
                        <textarea name="notes" 
                                  id="notes" 
                                  rows="3" 
                                  class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter any internal notes..."></textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="send_to_supplier" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Send PO to supplier via email</span>
                            </label>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('dashboard') }}" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </a>
                            <button type="submit" name="action" value="draft" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Save as Draft
                            </button>
                            <button type="submit" name="action" value="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <span class="flex items-center">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Create Purchase Order
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Info Panel -->
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Tip:</strong> Purchase orders can be linked to approved requisitions for better tracking. All PO activities are automatically logged in the audit trail.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function purchaseOrderForm() {
            return {
                init() {
                    console.log('Purchase order form initialized');
                }
            }
        }
    </script>
</body>
</html>
