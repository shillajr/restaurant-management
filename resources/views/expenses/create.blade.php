@extends('layouts.app')

@section('title', 'Record Expense')

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-10" x-data="expenseForm({
    quantity: {{ json_encode(old('quantity', 1)) }},
    unitPrice: {{ json_encode(old('unit_price', 0)) }},
    currency: {{ json_encode(['code' => config('finance.currency_code'), 'symbol' => config('finance.currency_symbol')]) }}
})">
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Record Expense</h1>
            <p class="mt-2 text-sm text-gray-600">Capture a single expense with manual item details and proof of purchase.</p>
        </div>
        <a href="{{ route('expenses.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
            <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to expenses
        </a>
    </div>

    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <form action="{{ route('expenses.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-8">
            @csrf

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-red-800">Please fix the highlighted issues.</h3>
                            <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <div class="grid gap-6 md:grid-cols-2">
                <div class="space-y-4">
                    <div>
                        <label for="expense_date" class="block text-sm font-medium text-gray-700">Expense Date <span class="text-red-500">*</span></label>
                        <input type="date" name="expense_date" id="expense_date" value="{{ old('expense_date', now()->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                        <select name="category" id="category" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Select a category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="payment_method" class="block text-sm font-medium text-gray-700">Payment Method <span class="text-red-500">*</span></label>
                        <select name="payment_method" id="payment_method" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                            <option value="">Select a method</option>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method }}" @selected(old('payment_method') === $method)>{{ $method }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="invoice_number" class="block text-sm font-medium text-gray-700">Invoice / Reference</label>
                        <input type="text" name="invoice_number" id="invoice_number" value="{{ old('invoice_number') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Optional reference number">
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="vendor_id" class="block text-sm font-medium text-gray-700">Vendor</label>
                        <div class="mt-1 flex gap-3">
                            <select name="vendor_id" id="vendor_id" class="flex-1 rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select a vendor</option>
                                @foreach ($vendors as $vendor)
                                    <option value="{{ $vendor->id }}" @selected((string) old('vendor_id') === (string) $vendor->id)>{{ $vendor->name }}</option>
                                @endforeach
                            </select>
                            <a href="{{ route('settings', ['tab' => 'vendors']) }}" class="inline-flex items-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50" target="_blank" rel="noopener">
                                + Add Vendor
                            </a>
                        </div>
                    </div>

                    <div>
                        <label for="proof" class="block text-sm font-medium text-gray-700">Proof of Purchase</label>
                        <input type="file" name="proof" id="proof" accept="image/*,.pdf" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-blue-700 hover:file:bg-blue-100">
                        <p class="mt-1 text-xs text-gray-500">Attach receipts or invoices (PDF, JPG, PNG up to 10 MB).</p>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Optional context or comments">{{ old('description') }}</textarea>
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-200 pt-6 space-y-6">
                <h2 class="text-lg font-semibold text-gray-900">Item Details</h2>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="md:col-span-3">
                        <label for="item_name" class="block text-sm font-medium text-gray-700">Item Name <span class="text-red-500">*</span></label>
                        <input type="text" name="item_name" id="item_name" value="{{ old('item_name') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500" required placeholder="Describe the item or service">
                    </div>

                    <div>
                        <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity <span class="text-red-500">*</span></label>
                        <input type="number" name="quantity" id="quantity" min="0.01" step="0.01" value="{{ old('quantity', 1) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500" required @input="quantity = parseFloat($event.target.value) || 0">
                    </div>

                    <div>
                        <label for="unit_price" class="block text-sm font-medium text-gray-700">Unit Price <span class="text-red-500">*</span></label>
                        <input type="number" name="unit_price" id="unit_price" min="0" step="0.01" value="{{ old('unit_price', 0) }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500" required @input="unitPrice = parseFloat($event.target.value) || 0">
                    </div>

                    <div class="flex items-end">
                        <div class="w-full">
                            <span class="block text-sm font-medium text-gray-700">Total</span>
                            <span class="mt-1 block text-lg font-semibold text-blue-600" x-text="formatCurrency(total)"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-4 border-t border-gray-200 pt-6">
                <a href="{{ route('expenses.index') }}" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50">Cancel</a>
                <button type="submit" class="inline-flex items-center rounded-md border border-transparent px-5 py-2 text-sm font-medium text-white bg-blue-600 shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2" :disabled="total <= 0" :class="{ 'opacity-50 cursor-not-allowed': total <= 0 }">
                    Save Expense
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    const fallbackCurrency = @json([
        'code' => config('finance.currency_code'),
        'symbol' => config('finance.currency_symbol'),
    ]);

    function expenseForm(initial) {
        return {
            quantity: parseFloat(initial.quantity) || 0,
            unitPrice: parseFloat(initial.unitPrice) || 0,
            currency: initial.currency || fallbackCurrency,
            get total() {
                return (this.quantity || 0) * (this.unitPrice || 0);
            },
            formatCurrency(value) {
                const amount = parseFloat(value ?? 0) || 0;
                const symbol = this.currency.symbol ?? fallbackCurrency.symbol;
                return `${symbol} ${amount.toFixed(2)}`;
            }
        };
    }
</script>
@endpush
@endsection
