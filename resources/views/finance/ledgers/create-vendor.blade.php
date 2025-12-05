@extends('layouts.app')

@section('title', 'Record Vendor Debt')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10" x-data="vendorDebtPage()" x-init="init()">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Record Vendor Debt</h1>
                <p class="mt-1 text-sm text-gray-500">Link an approved purchase order and capture the items supplied on credit.</p>
            </div>
            <a
                href="{{ route('financial-ledgers.index') }}"
                class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-amber-200"
            >
                Back to Ledgers
            </a>
        </div>

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

        <div class="rounded-xl border border-amber-100 bg-white p-6 shadow-sm">
            <form method="POST" action="{{ route('financial-ledgers.store') }}" class="space-y-5">
                @csrf
                <input type="hidden" name="entry_type" value="vendor_debt">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="sm:col-span-2">
                        <label for="purchase_order_id" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Purchase Order</label>
                        <select
                            id="purchase_order_id"
                            name="purchase_order_id"
                            x-model="vendorForm.purchaseOrderId"
                            class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                            :disabled="purchaseOrders.length === 0"
                            required
                        >
                            <option value="">Select purchase order</option>
                            <template x-for="po in purchaseOrders" :key="po.id">
                                <option :value="po.id" x-text="po.po_number + ' • ' + (po.vendor_names.length ? po.vendor_names.join(', ') : 'Multiple vendors')"></option>
                            </template>
                        </select>
                        <p
                            class="mt-1 text-xs text-gray-500"
                            x-show="selectedPurchaseOrder"
                            x-text="formatCurrency(selectedPurchaseOrder.grand_total) + ' total value'"
                        ></p>
                    </div>
                </div>

                <template x-if="purchaseOrders.length === 0">
                    <div class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                        There are no approved purchase orders available to link right now.
                    </div>
                </template>

                <template x-if="selectedPurchaseOrder && availableItems.length">
                    <div class="space-y-3">
                        <p class="text-xs font-semibold uppercase text-gray-500">Select items supplied on credit</p>
                        <div class="max-h-72 overflow-y-auto rounded-md border border-gray-200">
                            <ul class="divide-y divide-gray-200 bg-white">
                                <template x-for="item in availableItems" :key="item.key">
                                    <li class="flex items-start gap-3 px-4 py-3">
                                        <input
                                            type="checkbox"
                                            name="po_item_keys[]"
                                            :value="item.key"
                                            x-model="vendorForm.selectedItems"
                                            class="mt-1 h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                        >
                                        <div class="flex-1 text-sm text-gray-700">
                                            <p class="font-medium text-gray-900" x-text="item.label"></p>
                                            <p class="text-xs text-gray-500">
                                                <span x-text="formatQuantity(item.quantity, item.unit)"></span>
                                                <span class="mx-1 text-gray-300">•</span>
                                                <span x-text="formatCurrency(item.line_total)"></span>
                                            </p>
                                            <p class="text-xs text-gray-400" x-show="item.vendor" x-text="'Vendor: ' + item.vendor"></p>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </template>

                <template x-if="selectedPurchaseOrder && availableItems.length === 0">
                    <div class="rounded-md border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                        This purchase order has no line items with pricing to record on credit.
                    </div>
                </template>

                <div class="rounded-md bg-gray-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase text-gray-500">Total on credit</p>
                    <p class="mt-1 text-lg font-semibold text-gray-900" x-text="formatCurrency(vendorTotal)"></p>
                    <p class="mt-1 text-xs text-gray-500" x-text="reminderHelpText"></p>
                </div>

                <div>
                    <label for="vendor_notes" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Notes</label>
                    <textarea
                        id="vendor_notes"
                        name="notes"
                        rows="3"
                        x-model="vendorForm.notes"
                        class="w-full rounded-md border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200"
                        placeholder="Optional reference or reminder"
                    >{{ old('notes') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <a
                        href="{{ route('financial-ledgers.index') }}"
                        class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-600 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-amber-200"
                    >
                        Cancel
                    </a>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-200"
                        :disabled="vendorForm.selectedItems.length === 0"
                        :class="{ 'opacity-60 cursor-not-allowed': vendorForm.selectedItems.length === 0 }"
                    >
                        Record Vendor Debt
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    window.__vendorLedgerConfig = {
        purchaseOrders: @json($purchaseOrdersPayload),
        currency: @json(['code' => $currencyCode, 'symbol' => $currencySymbol]),
        reminderCadenceDays: @json($reminderCadenceDays),
        oldInputs: {
            purchase_order_id: @json(old('purchase_order_id')),
            selected_items: @json(old('po_item_keys', [])),
            notes: @json(old('notes')),
        },
    };
</script>
<script>
    (function () {
        const resolveConfig = () => window.__vendorLedgerConfig || {};
        const safeArray = (value) => Array.isArray(value) ? value : [];
        const toStringArray = (list) => safeArray(list).map((value) => String(value));
        const normalize = (value, fallback = '') => {
            if (typeof value === 'number') {
                return value.toString();
            }

            return typeof value === 'string' ? value : (value ?? fallback);
        };

        window.vendorDebtPage = () => {
            const config = resolveConfig();
            const oldInputs = config.oldInputs || {};

            const fallbackCurrency = {
                code: normalize(config.currency?.code, @json($currencyCode)),
                symbol: normalize(config.currency?.symbol, @json($currencySymbol)),
            };

            return {
                purchaseOrders: Array.isArray(config.purchaseOrders) ? config.purchaseOrders : [],
                currency: fallbackCurrency,
                reminderCadenceDays: Number(config.reminderCadenceDays) || @json($reminderCadenceDays),
                vendorForm: {
                    purchaseOrderId: normalize(oldInputs.purchase_order_id),
                    selectedItems: toStringArray(oldInputs.selected_items),
                    notes: normalize(oldInputs.notes),
                },
                init() {
                    this.$watch('vendorForm.purchaseOrderId', (value, oldValue) => {
                        if (typeof oldValue !== 'undefined' && String(value) !== String(oldValue)) {
                            this.vendorForm.selectedItems = [];
                        }

                        if (value) {
                            this.ensureSelectedItemsStillValid();
                        }
                    });

                    if (!this.vendorForm.purchaseOrderId && this.purchaseOrders.length === 1) {
                        this.vendorForm.purchaseOrderId = String(this.purchaseOrders[0].id);
                    }

                    if (this.vendorForm.purchaseOrderId) {
                        this.ensureSelectedItemsStillValid();
                    }
                },
                get selectedPurchaseOrder() {
                    const id = this.vendorForm.purchaseOrderId;
                    if (!id) {
                        return null;
                    }

                    return this.purchaseOrders.find((po) => String(po.id) === String(id)) || null;
                },
                get availableItems() {
                    const po = this.selectedPurchaseOrder;
                    if (!po || !Array.isArray(po.items)) {
                        return [];
                    }

                    return po.items;
                },
                get vendorTotal() {
                    const selected = new Set((this.vendorForm.selectedItems || []).map(String));

                    return this.availableItems
                        .filter((item) => selected.has(String(item.key)))
                        .reduce((sum, item) => sum + Number(item.line_total || 0), 0);
                },
                get reminderHelpText() {
                    return `Reminder emails will be scheduled every ${this.reminderCadenceDays} day${this.reminderCadenceDays === 1 ? '' : 's'} until the debt is cleared.`;
                },
                formatCurrency(value) {
                    const amount = Number(value || 0);

                    if (Number.isNaN(amount)) {
                        return `${this.currency.symbol} 0.00`;
                    }

                    try {
                        return new Intl.NumberFormat(undefined, { style: 'currency', currency: this.currency.code }).format(amount);
                    } catch (error) {
                        return `${this.currency.symbol} ${amount.toFixed(2)}`;
                    }
                },
                formatQuantity(quantity, unit) {
                    const amount = Number(quantity || 0);
                    const base = Number.isInteger(amount) ? amount.toString() : amount.toFixed(2);
                    return unit ? `${base} ${unit}` : base;
                },
                ensureSelectedItemsStillValid() {
                    const allowed = this.availableItems.map((item) => String(item.key));
                    this.vendorForm.selectedItems = (this.vendorForm.selectedItems || [])
                        .map(String)
                        .filter((key) => allowed.includes(key));
                },
            };
        };
    })();
</script>
@endpush
