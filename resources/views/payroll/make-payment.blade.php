@extends('layouts.app')

@section('title', 'Make Payment')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10" x-data="paymentForm()">
    <div class="mx-auto max-w-5xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Make Payment</h1>
                <p class="mt-1 text-sm text-gray-600">
                    Record payment for {{ $payroll->employee->name }} - {{ \Carbon\Carbon::parse($payroll->month)->format('F Y') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('payroll.show', $payroll->id) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Cancel
                </a>
            </div>
        </div>

        @if($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Payment Details</h2>
                    </div>
                    <form method="POST" action="{{ route('payroll.payment.store', $payroll->id) }}" class="space-y-6 p-6">
                        @csrf

                        <div>
                            <label for="amount" class="mb-2 block text-sm font-medium text-gray-700">
                                Payment Amount <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <span class="text-gray-500 sm:text-sm">{{ currency_label() }}</span>
                                </div>
                                <input
                                    type="number"
                                    id="amount"
                                    name="amount"
                                    x-model="amount"
                                    @input="calculateRemaining()"
                                    step="0.01"
                                    min="0.01"
                                    max="{{ $payroll->outstanding_balance }}"
                                    value="{{ old('amount') }}"
                                    class="block w-full rounded-lg border border-gray-300 py-3 pl-12 pr-12 text-lg focus:border-transparent focus:ring-2 focus:ring-indigo-500"
                                    placeholder="0.00"
                                    required
                                >
                            </div>
                            <div class="mt-2 flex items-center justify-between text-sm">
                                <p class="text-gray-500">Maximum: {{ currency_format($payroll->outstanding_balance) }}</p>
                                <button
                                    type="button"
                                    @click="amount = outstandingBalance; calculateRemaining()"
                                    class="font-medium text-indigo-600 transition-colors hover:text-indigo-800"
                                >
                                    Pay Full Amount
                                </button>
                            </div>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment_date" class="mb-2 block text-sm font-medium text-gray-700">
                                Payment Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                id="payment_date"
                                name="payment_date"
                                value="{{ old('payment_date', date('Y-m-d')) }}"
                                max="{{ date('Y-m-d') }}"
                                class="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-indigo-500"
                                required
                            >
                            @error('payment_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment_method" class="mb-2 block text-sm font-medium text-gray-700">
                                Payment Method
                            </label>
                            <select
                                id="payment_method"
                                name="payment_method"
                                class="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="">Select payment method</option>
                                <option value="Cash" {{ old('payment_method') == 'Cash' ? 'selected' : '' }}>Cash</option>
                                <option value="Bank Transfer" {{ old('payment_method') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="Mobile Money" {{ old('payment_method') == 'Mobile Money' ? 'selected' : '' }}>Mobile Money</option>
                                <option value="Cheque" {{ old('payment_method') == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                                <option value="Other" {{ old('payment_method') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="payment_reference" class="mb-2 block text-sm font-medium text-gray-700">
                                Payment Reference / Transaction ID
                            </label>
                            <input
                                type="text"
                                id="payment_reference"
                                name="payment_reference"
                                value="{{ old('payment_reference') }}"
                                class="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-indigo-500"
                                placeholder="e.g., TXN123456, Cheque #7890"
                            >
                            <p class="mt-1 text-xs text-gray-500">Transaction ID, cheque number, or other reference</p>
                            @error('payment_reference')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="notes" class="mb-2 block text-sm font-medium text-gray-700">
                                Notes
                            </label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="3"
                                class="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-indigo-500"
                                placeholder="Add any additional notes about this payment..."
                            >{{ old('notes') }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <label class="flex items-start">
                                <input
                                    type="checkbox"
                                    name="send_notification"
                                    value="1"
                                    checked
                                    class="mt-1 h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                >
                                <div class="ml-3">
                                    <span class="text-sm font-medium text-blue-900">Send email notification to employee</span>
                                    <p class="mt-1 text-xs text-blue-700">
                                        An email will be sent to {{ $payroll->employee->email }} with payment details
                                    </p>
                                </div>
                            </label>
                        </div>

                        <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                            <a href="{{ route('payroll.show', $payroll->id) }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                                Cancel
                            </a>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!amount || amount <= 0 || amount > outstandingBalance"
                            >
                                Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">Payroll Summary</h3>
                    </div>
                    <div class="space-y-4 p-6">
                        <div>
                            <p class="text-sm text-gray-500">Employee</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">{{ $payroll->employee->name }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Month</p>
                            <p class="mt-1 text-base font-semibold text-gray-900">
                                {{ \Carbon\Carbon::parse($payroll->month)->format('F Y') }}
                            </p>
                        </div>

                        <div class="border-t border-gray-200 pt-3">
                            <p class="text-sm text-gray-500">Total Due</p>
                            <p class="mt-1 text-lg font-bold text-gray-900">
                                {{ currency_format($payroll->total_due) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-gray-500">Already Paid</p>
                            <p class="mt-1 text-lg font-semibold text-green-600">
                                {{ currency_format($payroll->total_paid) }}
                            </p>
                        </div>

                        <div class="border-t border-gray-200 pt-3">
                            <p class="text-sm text-gray-500">Outstanding Balance</p>
                            <p class="mt-1 text-xl font-bold text-red-600">
                                {{ currency_format($payroll->outstanding_balance) }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-gradient-to-br from-green-500 to-teal-600 p-6 text-white shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">Payment Preview</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between border-b border-green-400 pb-3">
                            <span class="text-sm">Payment Amount</span>
                            <span class="text-lg font-bold" x-text="formatCurrency(amount || 0)"></span>
                        </div>

                        <div class="flex items-center justify-between border-b border-green-400 pb-3">
                            <span class="text-sm">Current Outstanding</span>
                            <span class="text-base">{{ currency_format($payroll->outstanding_balance) }}</span>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <span class="text-sm font-medium">New Balance</span>
                            <span class="text-2xl font-bold" x-text="formatCurrency(remainingBalance())"></span>
                        </div>

                        <template x-if="remainingBalance() <= 0">
                            <div class="mt-4 flex items-center justify-center rounded-lg bg-white bg-opacity-20 p-3">
                                <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="font-semibold">Will be fully paid</span>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-yellow-800">Quick Tips</h4>
                            <ul class="mt-2 list-disc list-inside space-y-1 text-xs text-yellow-700">
                                <li>You can make partial payments</li>
                                <li>Payment reference helps with tracking</li>
                                <li>Notification is sent automatically</li>
                                <li>All transactions are logged</li>
                            </ul>
                        </div>
                    </div>
                </div>

                @if($payroll->payments->count() > 0)
                    <div class="rounded-lg bg-white p-6 shadow">
                        <h3 class="mb-2 text-sm font-medium text-gray-500">Previous Payments</h3>
                        <p class="text-2xl font-bold text-gray-900">{{ $payroll->payments->count() }}</p>
                        <a href="{{ route('payroll.show', $payroll->id) }}" class="mt-2 inline-flex items-center text-sm font-medium text-indigo-600 transition-colors hover:text-indigo-800">
                            View payment history →
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function paymentForm() {
        return {
            amount: {{ old('amount', '') ?: 0 }},
            outstandingBalance: {{ $payroll->outstanding_balance }},
            currencyMeta: window.appCurrency || { code: 'USD', symbol: '$', precision: 2 },

            calculateRemaining() {
                this.amount = parseFloat(this.amount) || 0;
                if (this.amount > this.outstandingBalance) {
                    this.amount = this.outstandingBalance;
                }
                if (this.amount < 0) {
                    this.amount = 0;
                }
            },

            formatNumber(value) {
                return parseFloat(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            get currencyLabel() {
                return this.currencyMeta.code;
            },

            formatCurrency(value) {
                const amount = parseFloat(value ?? 0) || 0;
                const precision = Number.isInteger(this.currencyMeta.precision) ? this.currencyMeta.precision : 2;
                const formatted = Math.abs(amount).toLocaleString('en-US', {
                    minimumFractionDigits: precision,
                    maximumFractionDigits: precision,
                });
                const sign = amount < 0 ? '-' : '';

                return `${sign}${this.currencyLabel} ${formatted}`;
            },

            remainingBalance() {
                return Math.max(this.outstandingBalance - (parseFloat(this.amount) || 0), 0);
            }
        };
    }
</script>
@endpush
