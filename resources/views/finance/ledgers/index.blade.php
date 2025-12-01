@extends('layouts.app')

@section('title', __('navigation.links.financial_ledgers'))

@section('content')
<div
    class="px-4 py-8 sm:px-6 lg:px-10"
    x-data="financialLedgerPage()"
    x-init="init()"
    x-on:keydown.escape.window="closeAllModals()"
>
    <div class="mx-auto max-w-7xl space-y-8">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('navigation.links.financial_ledgers') }}</h1>
                <p class="text-sm text-gray-500">Monitor all vendor liabilities and customer receivables in one place.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">We ran into a few issues:</p>
                <ul class="ml-4 list-disc space-y-1 pt-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-6 xl:grid-cols-4">
            <div class="space-y-6 xl:col-span-3">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="rounded-xl border border-indigo-100 bg-white p-4">
                        <p class="text-xs font-semibold uppercase text-indigo-500">Total Outstanding</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">{{ currency_format($stats['total_outstanding']) }}</p>
                        <p class="text-xs text-gray-500">{{ currency_format($stats['total_principal']) }} principal · {{ currency_format($stats['total_paid']) }} settled</p>
                    </div>
                    <div class="rounded-xl border border-amber-100 bg-white p-4">
                        <p class="text-xs font-semibold uppercase text-amber-500">Vendor Liabilities</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">{{ currency_format($stats['liability_outstanding']) }}</p>
                        <p class="text-xs text-gray-500">Outstanding owed to suppliers</p>
                    </div>
                    <div class="rounded-xl border border-emerald-100 bg-white p-4">
                        <p class="text-xs font-semibold uppercase text-emerald-500">Customer Receivables</p>
                        <p class="mt-2 text-2xl font-bold text-gray-900">{{ currency_format($stats['receivable_outstanding']) }}</p>
                        <p class="text-xs text-gray-500">Outstanding owed by customers</p>
                    </div>
                </div>

                <div class="rounded-lg border border-dashed border-indigo-200 bg-indigo-50 p-4 shadow-sm">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Log New Entry</h2>
                            <p class="text-sm text-gray-600">Add a customer receivable or vendor debt without leaving the dashboard.</p>
                        </div>
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                                @click="openCustomerModal()"
                            >
                                Add Customer Receivable
                            </button>
                            <a
                                href="{{ route('financial-ledgers.vendor.create') }}"
                                class="inline-flex items-center justify-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-200"
                            >
                                Add Vendor Debt
                            </a>
                        </div>
                    </div>
                </div>

                <form method="GET" class="rounded-lg border border-gray-200 bg-white p-4">
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <label for="search" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Search</label>
                            <input id="search" name="search" type="text" value="{{ $filters['search'] ?? '' }}" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200" placeholder="Ledger code or contact">
                        </div>
                        <div>
                            <label for="type" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Type</label>
                            <select id="type" name="type" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                <option value="">All</option>
                                <option value="{{ \App\Models\FinancialLedger::TYPE_LIABILITY }}" @selected(($filters['type'] ?? null) === \App\Models\FinancialLedger::TYPE_LIABILITY)>Vendor liabilities</option>
                                <option value="{{ \App\Models\FinancialLedger::TYPE_RECEIVABLE }}" @selected(($filters['type'] ?? null) === \App\Models\FinancialLedger::TYPE_RECEIVABLE)>Customer receivables</option>
                            </select>
                        </div>
                        <div>
                            <label for="status" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Status</label>
                            <select id="status" name="status" class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                <option value="">All</option>
                                <option value="{{ \App\Models\FinancialLedger::STATUS_OPEN }}" @selected(($filters['status'] ?? null) === \App\Models\FinancialLedger::STATUS_OPEN)>Open</option>
                                <option value="{{ \App\Models\FinancialLedger::STATUS_CLOSED }}" @selected(($filters['status'] ?? null) === \App\Models\FinancialLedger::STATUS_CLOSED)>Closed</option>
                                <option value="{{ \App\Models\FinancialLedger::STATUS_ARCHIVED }}" @selected(($filters['status'] ?? null) === \App\Models\FinancialLedger::STATUS_ARCHIVED)>Archived</option>
                            </select>
                        </div>
                        <div class="flex items-end gap-3">
                            <button type="submit" class="inline-flex w-full items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200">
                                Apply Filters
                            </button>
                            @if(array_filter($filters))
                                <a href="{{ route('financial-ledgers.index') }}" class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50">Clear</a>
                            @endif
                        </div>
                    </div>
                </form>

                <div class="rounded-lg border border-gray-200 bg-white">
                    <div class="border-b border-gray-200 px-4 py-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Ledger Activity</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr class="text-left">
                                    <th class="px-4 py-2 font-medium text-gray-500">Ledger</th>
                                    <th class="px-4 py-2 font-medium text-gray-500">Type</th>
                                    <th class="px-4 py-2 font-medium text-gray-500">Counterparty</th>
                                    <th class="px-4 py-2 font-medium text-gray-500">Opened</th>
                                    <th class="px-4 py-2 font-medium text-gray-500 text-right">Principal</th>
                                    <th class="px-4 py-2 font-medium text-gray-500 text-right">Received</th>
                                    <th class="px-4 py-2 font-medium text-gray-500 text-right">Outstanding</th>
                                    <th class="px-4 py-2 font-medium text-gray-500 text-right">Status</th>
                                    <th class="px-4 py-2 font-medium text-gray-500 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @forelse($ledgers as $ledger)
                                    @php
                                        $statusClasses = [
                                            \App\Models\FinancialLedger::STATUS_OPEN => 'bg-indigo-100 text-indigo-700',
                                            \App\Models\FinancialLedger::STATUS_CLOSED => 'bg-emerald-100 text-emerald-700',
                                            \App\Models\FinancialLedger::STATUS_ARCHIVED => 'bg-slate-200 text-slate-700',
                                        ];
                                        $statusLabel = ucfirst($ledger->status);
                                        $statusClass = $statusClasses[$ledger->status] ?? 'bg-gray-100 text-gray-700';
                                        $paymentMethodLabels = collect($paymentMethods)->pluck('label', 'value');
                                        $paymentsPayload = $ledger->payments
                                            ->sortByDesc('paid_at')
                                            ->take(10)
                                            ->values()
                                            ->map(function ($payment) use ($paymentMethodLabels) {
                                                return [
                                                    'id' => $payment->id,
                                                    'amount' => (float) $payment->amount,
                                                    'amount_formatted' => currency_format($payment->amount),
                                                    'paid_at' => optional($payment->paid_at)->toDateString(),
                                                    'paid_at_formatted' => optional($payment->paid_at)->format('M d, Y'),
                                                    'payment_method' => $payment->payment_method,
                                                    'payment_method_label' => $paymentMethodLabels[$payment->payment_method] ?? ucfirst(str_replace('_', ' ', (string) $payment->payment_method)),
                                                    'reference' => $payment->reference,
                                                    'notes' => $payment->notes,
                                                    'recorded_by' => optional($payment->recorder)->name,
                                                ];
                                            })
                                            ->all();
                                        $ledgerPayload = [
                                            'id' => $ledger->id,
                                            'ledger_code' => $ledger->ledger_code,
                                            'ledger_type' => $ledger->ledger_type,
                                            'ledger_type_label' => $ledger->ledger_type === \App\Models\FinancialLedger::TYPE_LIABILITY ? 'Vendor liability' : 'Customer receivable',
                                            'principal' => (float) $ledger->principal_amount,
                                            'paid' => (float) $ledger->paid_amount,
                                            'outstanding' => (float) $ledger->outstanding_amount,
                                            'formatted' => [
                                                'principal' => currency_format($ledger->principal_amount),
                                                'paid' => currency_format($ledger->paid_amount),
                                                'outstanding' => currency_format($ledger->outstanding_amount),
                                            ],
                                            'status' => [
                                                'label' => $statusLabel,
                                                'class' => $statusClass,
                                            ],
                                            'payments' => $paymentsPayload,
                                        ];
                                    @endphp
                                    <tr class="hover:bg-gray-50" data-ledger-row="{{ $ledger->id }}">
                                        <td class="px-4 py-3 font-medium text-gray-900">
                                            {{ $ledger->ledger_code }}
                                            @if($ledger->purchase_order_id)
                                                <span class="block text-xs text-gray-500">PO #{{ $ledger->purchase_order_id }}</span>
                                            @elseif($ledger->credit_sale_id)
                                                <span class="block text-xs text-gray-500">Credit sale #{{ $ledger->credit_sale_id }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            @if($ledger->ledger_type === \App\Models\FinancialLedger::TYPE_LIABILITY)
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700">Vendor liability</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-semibold text-emerald-700">Customer receivable</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            <span class="font-medium">{{ $ledger->vendor_name ?? 'N/A' }}</span>
                                            @if($ledger->contact_phone)
                                                <span class="block text-xs text-gray-500">☎ {{ $ledger->contact_phone }}</span>
                                            @endif
                                            @if($ledger->contact_email)
                                                <span class="block text-xs text-gray-500">✉ {{ $ledger->contact_email }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-gray-700">
                                            {{ optional($ledger->opened_at)->format('M d, Y') ?? '—' }}
                                            @if($ledger->next_reminder_due_at)
                                                <span class="block text-xs text-gray-500">Reminder {{ $ledger->next_reminder_due_at->format('M d') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900" data-ledger-principal="{{ $ledger->id }}">{{ currency_format($ledger->principal_amount) }}</td>
                                        <td class="px-4 py-3 text-right font-medium text-gray-900" data-ledger-paid="{{ $ledger->id }}">{{ currency_format($ledger->paid_amount) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold text-gray-900" data-ledger-outstanding="{{ $ledger->id }}">{{ currency_format($ledger->outstanding_amount) }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <span
                                                class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusClass }}"
                                                data-ledger-status="{{ $ledger->id }}"
                                                data-ledger-status-base="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            >{{ $statusLabel }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:opacity-60"
                                                x-on:click='openPaymentModal(@json($ledgerPayload))'
                                                data-ledger-payment-button="{{ $ledger->id }}"
                                                @disabled((float) $ledger->outstanding_amount <= 0)
                                            >
                                                Record Payment
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="px-4 py-12 text-center text-sm text-gray-500">
                                            <p class="font-medium">No ledgers to display yet.</p>
                                            <p class="mt-2 text-xs text-gray-400">Use the buttons above to add your first debt or credit entry.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($ledgers->hasPages())
                        <div class="border-t border-gray-200 px-4 py-3">
                            {{ $ledgers->links() }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg border border-gray-200 bg-white p-6">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-600">Upcoming reminders</h2>
                    <ul class="mt-4 space-y-3">
                        @forelse($upcomingReminders as $reminder)
                            <li class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                                <p class="text-sm font-semibold text-gray-900">{{ $reminder->ledger_code }}</p>
                                <p class="text-xs text-gray-500">{{ optional($reminder->next_reminder_due_at)->format('M d, Y') ?? 'Not scheduled' }}</p>
                                <p class="mt-1 text-xs text-gray-400">{{ $reminder->vendor_name ?? trim(($reminder->contact_first_name ?? '') . ' ' . ($reminder->contact_last_name ?? '')) ?: 'Counterparty unknown' }}</p>
                            </li>
                        @empty
                            <li class="rounded-lg border border-dashed border-gray-200 px-4 py-6 text-center text-xs text-gray-400">
                                No reminders scheduled yet.
                            </li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div
        x-show="customerModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center px-4 py-10"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-gray-900/40" @click="closeCustomerModal()"></div>
        <div class="relative z-10 w-full max-w-lg rounded-xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Add Customer Receivable</h2>
                    <p class="mt-1 text-sm text-gray-500">Capture who owes you and the balance outstanding.</p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600" @click="closeCustomerModal()" aria-label="Close">
                    &times;
                </button>
            </div>

            <form method="POST" action="{{ route('financial-ledgers.store') }}" class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="entry_type" value="customer_receivable">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="customer_first_name" class="mb-1 block text-xs font-semibold uppercase text-gray-500">First Name</label>
                        <input
                            id="customer_first_name"
                            name="customer_first_name"
                            type="text"
                            x-model="customerForm.firstName"
                            value="{{ old('customer_first_name') }}"
                            class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                            required
                        >
                    </div>
                    <div>
                        <label for="customer_last_name" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Last Name</label>
                        <input
                            id="customer_last_name"
                            name="customer_last_name"
                            type="text"
                            x-model="customerForm.lastName"
                            value="{{ old('customer_last_name') }}"
                            class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                            required
                        >
                    </div>
                </div>

                <div>
                    <label for="customer_phone" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Phone Number</label>
                    <input
                        id="customer_phone"
                        name="customer_phone"
                        type="text"
                        x-model="customerForm.phone"
                        value="{{ old('customer_phone') }}"
                        class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        placeholder="Include country code"
                        required
                    >
                </div>

                <div>
                    <label for="customer_amount" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Amount</label>
                    <input
                        id="customer_amount"
                        name="amount"
                        type="number"
                        step="0.01"
                        min="0"
                        x-model="customerForm.amount"
                        value="{{ old('amount') }}"
                        class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        required
                    >
                </div>

                <div>
                    <label for="customer_notes" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Notes</label>
                    <textarea
                        id="customer_notes"
                        name="notes"
                        rows="3"
                        x-model="customerForm.notes"
                        class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        placeholder="Optional reference or reminder"
                    >{{ old('notes') }}</textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                        @click="closeCustomerModal()"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200"
                    >
                        Record Receivable
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div
        x-show="paymentModalOpen"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center px-4 py-10"
        role="dialog"
        aria-modal="true"
    >
        <div class="absolute inset-0 bg-gray-900/40" @click="closePaymentModal()"></div>
        <div class="relative z-10 w-full max-w-xl rounded-xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Record Payment</h2>
                    <p class="mt-1 text-sm text-gray-500" x-show="paymentLedger">
                        <span class="font-medium" x-text="paymentLedger?.ledger_code"></span>
                        <span class="mx-1 text-gray-300">•</span>
                        <span x-text="paymentLedger?.ledger_type_label"></span>
                    </p>
                </div>
                <button type="button" class="text-gray-400 hover:text-gray-600" @click="closePaymentModal()" aria-label="Close">
                    &times;
                </button>
            </div>

            <div class="mt-4 rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-600" x-show="paymentLedger">
                <div class="flex items-center justify-between">
                    <span class="font-medium">Principal</span>
                    <span x-text="paymentLedger?.formatted?.principal"></span>
                </div>
                <div class="mt-1 flex items-center justify-between">
                    <span class="font-medium">Received</span>
                    <span x-text="paymentLedger?.formatted?.paid"></span>
                </div>
                <div class="mt-1 flex items-center justify-between text-amber-600">
                    <span class="font-semibold">Outstanding</span>
                    <span class="font-semibold" x-text="paymentLedger?.formatted?.outstanding"></span>
                </div>
            </div>

            <form class="mt-4 space-y-4" @submit.prevent="submitPayment()">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="payment_amount" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Amount</label>
                        <input
                            id="payment_amount"
                            type="number"
                            step="0.01"
                            min="0"
                            x-model="paymentForm.amount"
                            class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            required
                        >
                    </div>
                    <div>
                        <label for="payment_paid_at" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Paid On</label>
                        <input
                            id="payment_paid_at"
                            type="date"
                            x-model="paymentForm.paidAt"
                            class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        >
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="payment_method" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Method</label>
                        <select
                            id="payment_method"
                            x-model="paymentForm.paymentMethod"
                            class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            required
                        >
                            <template x-for="method in paymentMethods" :key="method.value">
                                <option :value="method.value" x-text="method.label"></option>
                            </template>
                        </select>
                    </div>
                    <div>
                        <label for="payment_reference" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Reference</label>
                        <input
                            id="payment_reference"
                            type="text"
                            x-model="paymentForm.reference"
                            class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                            placeholder="Optional"
                        >
                    </div>
                </div>

                <div>
                    <label for="payment_notes" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Notes</label>
                    <textarea
                        id="payment_notes"
                        rows="3"
                        x-model="paymentForm.notes"
                        class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        placeholder="Optional context"
                    ></textarea>
                </div>

                <div x-show="paymentError" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700" x-text="paymentError"></div>
                <div x-show="paymentSuccess" class="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-medium text-emerald-700" x-text="paymentSuccess"></div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-200"
                        @click="closePaymentModal()"
                    >
                        Close
                    </button>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-200 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="paymentSubmitting"
                    >
                        <span x-show="!paymentSubmitting">Record Payment</span>
                        <span x-show="paymentSubmitting">Saving…</span>
                    </button>
                </div>
            </form>

            <div class="mt-6 border-t border-gray-200 pt-4">
                <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">Recent payments</h3>
                <template x-if="paymentLedger && paymentLedger.payments && paymentLedger.payments.length">
                    <ul class="mt-3 space-y-2">
                        <template x-for="payment in paymentLedger.payments" :key="payment.id">
                            <li class="rounded-lg border border-gray-200 px-3 py-2">
                                <div class="flex items-center justify-between text-sm font-medium text-gray-900">
                                    <span x-text="payment.amount_formatted"></span>
                                    <span class="text-xs text-gray-500" x-text="payment.paid_at_formatted || payment.paid_at"></span>
                                </div>
                                <div class="mt-1 text-xs text-gray-500">
                                    <span class="font-medium" x-text="payment.payment_method_label"></span>
                                    <template x-if="payment.reference">
                                        <span class="ml-2" x-text="'Ref: ' + payment.reference"></span>
                                    </template>
                                </div>
                                <template x-if="payment.notes">
                                    <p class="mt-1 text-xs text-gray-500" x-text="payment.notes"></p>
                                </template>
                                <template x-if="payment.recorded_by">
                                    <p class="mt-1 text-xs text-gray-400" x-text="'Logged by ' + payment.recorded_by"></p>
                                </template>
                            </li>
                        </template>
                    </ul>
                </template>
                <template x-if="!paymentLedger || !paymentLedger.payments || paymentLedger.payments.length === 0">
                    <p class="mt-3 text-xs text-gray-400">No payments logged yet.</p>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    window.__financialLedgerConfig = {
        initialEntryType: @json(old('entry_type')),
        oldCustomerInputs: {
            customer_first_name: @json(old('customer_first_name')),
            customer_last_name: @json(old('customer_last_name')),
            customer_phone: @json(old('customer_phone')),
            amount: @json(old('amount')),
            notes: @json(old('notes')),
        },
        paymentMethods: @json($paymentMethods),
    };
</script>
<script>
    (function () {
        const resolveConfig = () => window.__financialLedgerConfig || {};

        window.financialLedgerPage = () => {
            const config = resolveConfig();
            const initialType = config.initialEntryType;
            const oldInputs = config.oldCustomerInputs || {};
            const normalize = (value, fallback = '') => (typeof value === 'string' ? value : (value ?? fallback));
            const paymentMethods = Array.isArray(config.paymentMethods) ? config.paymentMethods : [];

            return {
                customerModalOpen: initialType === 'customer_receivable',
                customerForm: {
                    firstName: initialType === 'customer_receivable' ? normalize(oldInputs.customer_first_name) : '',
                    lastName: initialType === 'customer_receivable' ? normalize(oldInputs.customer_last_name) : '',
                    phone: initialType === 'customer_receivable' ? normalize(oldInputs.customer_phone) : '',
                    amount: initialType === 'customer_receivable' ? normalize(oldInputs.amount) : '',
                    notes: initialType === 'customer_receivable' ? normalize(oldInputs.notes) : '',
                },
                paymentModalOpen: false,
                paymentMethods,
                paymentLedger: null,
                paymentForm: {
                    ledgerId: null,
                    amount: '',
                    paidAt: '',
                    paymentMethod: paymentMethods.length ? paymentMethods[0].value : '',
                    reference: '',
                    notes: '',
                },
                paymentError: '',
                paymentSuccess: '',
                paymentSubmitting: false,
                init() {
                    if (!this.customerModalOpen) {
                        this.customerForm.notes = '';
                    }

                    if (this.customerModalOpen) {
                        document.body.classList.add('overflow-hidden');
                    }
                },
                openCustomerModal() {
                    this.closePaymentModal();
                    this.customerModalOpen = true;
                    document.body.classList.add('overflow-hidden');
                },
                closeCustomerModal() {
                    this.customerModalOpen = false;
                    this.resetBodyScroll();
                },
                closeAllModals() {
                    this.closeCustomerModal();
                    this.closePaymentModal();
                },
                resetBodyScroll() {
                    if (!this.customerModalOpen && !this.paymentModalOpen) {
                        document.body.classList.remove('overflow-hidden');
                    }
                },
                openPaymentModal(ledger) {
                    const clonedLedger = JSON.parse(JSON.stringify(ledger));
                    this.paymentLedger = clonedLedger;
                    this.paymentModalOpen = true;
                    this.paymentError = '';
                    this.paymentSuccess = '';
                    this.paymentSubmitting = false;
                    this.paymentForm = {
                        ledgerId: clonedLedger.id,
                        amount: clonedLedger.outstanding > 0 ? Number(clonedLedger.outstanding).toFixed(2) : '',
                        paidAt: this.today(),
                        paymentMethod: this.paymentMethods.length ? this.paymentMethods[0].value : '',
                        reference: '',
                        notes: '',
                    };
                    this.customerModalOpen = false;
                    document.body.classList.add('overflow-hidden');
                },
                closePaymentModal() {
                    this.paymentModalOpen = false;
                    this.paymentLedger = null;
                    this.paymentError = '';
                    this.paymentSuccess = '';
                    this.paymentSubmitting = false;
                    this.resetBodyScroll();
                },
                async submitPayment() {
                    if (!this.paymentLedger || !this.paymentForm.ledgerId) {
                        return;
                    }

                    this.paymentSubmitting = true;
                    this.paymentError = '';
                    this.paymentSuccess = '';

                    try {
                        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                        const response = await fetch(`/finance/ledgers/${this.paymentForm.ledgerId}/payments`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': token,
                            },
                            body: JSON.stringify({
                                amount: this.paymentForm.amount,
                                paid_at: this.paymentForm.paidAt,
                                payment_method: this.paymentForm.paymentMethod,
                                reference: this.paymentForm.reference,
                                notes: this.paymentForm.notes,
                            }),
                        });

                        const data = await response.json();

                        if (!response.ok) {
                            const errorMessage = data?.message || this.extractFirstError(data?.errors);
                            this.paymentError = errorMessage || 'Unable to record payment.';
                            return;
                        }

                        this.paymentSuccess = data?.message || 'Payment recorded successfully.';
                        if (data?.ledger) {
                            this.paymentLedger.outstanding = data.ledger.outstanding;
                            this.paymentLedger.paid = data.ledger.paid;
                            this.paymentLedger.formatted = data.ledger.formatted;
                            this.paymentLedger.status = data.ledger.status;
                            this.paymentLedger.payments = data.ledger.payments || [];
                            this.updateLedgerRow(data.ledger);
                        }

                        if (this.paymentLedger.outstanding > 0) {
                            this.paymentForm.amount = Number(this.paymentLedger.outstanding).toFixed(2);
                        } else {
                            this.paymentForm.amount = '';
                        }

                        this.paymentForm.reference = '';
                        this.paymentForm.notes = '';
                    } catch (error) {
                        this.paymentError = 'Unable to record payment right now. Please try again.';
                    } finally {
                        this.paymentSubmitting = false;
                    }
                },
                updateLedgerRow(ledger) {
                    const paidEl = document.querySelector(`[data-ledger-paid="${ledger.id}"]`);
                    if (paidEl) {
                        paidEl.textContent = ledger.formatted?.paid ?? ledger.paid;
                    }

                    const outstandingEl = document.querySelector(`[data-ledger-outstanding="${ledger.id}"]`);
                    if (outstandingEl) {
                        outstandingEl.textContent = ledger.formatted?.outstanding ?? ledger.outstanding;
                    }

                    const statusEl = document.querySelector(`[data-ledger-status="${ledger.id}"]`);
                    if (statusEl) {
                        const base = statusEl.dataset.ledgerStatusBase || 'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold';
                        statusEl.className = `${base} ${ledger.status?.class ?? ''}`.trim();
                        statusEl.textContent = ledger.status?.label ?? '';
                    }

                    const buttonEl = document.querySelector(`[data-ledger-payment-button="${ledger.id}"]`);
                    if (buttonEl) {
                        const shouldDisable = Number(ledger.outstanding) <= 0;
                        buttonEl.disabled = shouldDisable;
                    }
                },
                extractFirstError(errors) {
                    if (!errors) {
                        return '';
                    }

                    const values = Object.values(errors).flat();
                    return values.length ? values[0] : '';
                },
                today() {
                    const date = new Date();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    return `${date.getFullYear()}-${month}-${day}`;
                },
            };
        };
    })();
</script>
@endpush
