<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $purchaseOrder = __('purchase_orders.create');
        $common = __('common');
    @endphp
    <title>{{ $purchaseOrder['page_title'] }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    @isset($vendors)
    <script>
        window.vendorContacts = @json(
            ($vendors ?? collect())->mapWithKeys(function($v){
                return [$v->name => ['email' => $v->email, 'phone' => $v->phone]];
            })
        );
    </script>
    @endisset
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">{{ $purchaseOrder['title'] }}</h1>
                    <p class="mt-2 text-sm text-gray-600">{{ $purchaseOrder['description'] }}</p>
                </div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    {{ $purchaseOrder['back_to_dashboard'] }}
                </a>
            </div>

            <!-- Form -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                                <form action="{{ route('purchase-orders.store') }}" method="POST" 
                                            x-data="purchaseOrderForm()"
                      class="p-6 space-y-6">
                    @csrf
                                        <!-- Bind selected requisition to submission -->
                                        <input type="hidden" name="requisition_id" :value="selectedRequisitionId">

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
                                    <h3 class="text-sm font-medium text-red-800">{{ $common['messages']['validation_errors'] }}</h3>
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
                            {{ $purchaseOrder['link_requisition']['label'] }}
                        </label>
                        <select name="requisition_id" id="requisition_id" 
                                x-model="selectedRequisitionId"
                                @change="loadRequisitionPreview()"
                                class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">{{ $purchaseOrder['link_requisition']['standalone_option'] }}</option>
                            @if(isset($approvedRequisitions))
                                @foreach($approvedRequisitions as $req)
                                    <option value="{{ $req->id }}">
                                        REQ-{{ str_pad($req->id, 3, '0', STR_PAD_LEFT) }} - {{ $req->note ?? $purchaseOrder['link_requisition']['approved_fallback'] }} {{ $purchaseOrder['link_requisition']['approved_suffix'] }}
                                        @if($req->purchaseOrder)
                                            {{ str_replace(':number', $req->purchaseOrder->po_number, $purchaseOrder['link_requisition']['po_exists']) }}
                                        @endif
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <p class="mt-1 text-xs text-gray-500">{{ $purchaseOrder['link_requisition']['hint'] }}</p>
                        @error('requisition_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Supplier Information -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">{{ $purchaseOrder['supplier_info']['heading'] }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Supplier Selection -->
                            <div>
                                <label for="supplier_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ $purchaseOrder['supplier_info']['supplier_label'] }} <span class="text-red-500">*</span>
                                </label>
                                <select name="supplier_id" id="supplier_id" 
                                        class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        :disabled="selectedRequisitionId">
                                    <option value="">{{ $purchaseOrder['supplier_info']['select_supplier'] }}</option>
                                    <option value="1">ABC Food Distributors</option>
                                    <option value="2">Fresh Farm Produce</option>
                                    <option value="3">Ocean Seafood Supply</option>
                                    <option value="4">Metro Restaurant Supply</option>
                                    <option value="5">Quality Meat Co.</option>
                                </select>
                                @error('supplier_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p x-show="selectedRequisitionId" class="mt-1 text-xs text-gray-500">{{ $purchaseOrder['supplier_info']['taken_from_requisition'] }}</p>
                            </div>

                            <!-- Assigned To -->
                            <div>
                                <label for="assigned_to" class="block text-sm font-medium text-gray-700 mb-2">
                                    {{ $purchaseOrder['supplier_info']['assign_label'] }} <span class="text-red-500">*</span>
                                </label>
                                <select name="assigned_to" id="assigned_to" 
                                        class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                        :disabled="selectedRequisitionId">
                                    <option value="">{{ $purchaseOrder['supplier_info']['select_purchaser'] }}</option>
                                    <option value="1">John Purchaser</option>
                                    <option value="2">Sarah Buyer</option>
                                </select>
                                @error('assigned_to')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                                <p x-show="selectedRequisitionId" class="mt-1 text-xs text-gray-500">{{ $purchaseOrder['supplier_info']['field_disabled'] }}</p>
                            </div>
                        </div>
                        
                        <!-- Vendor list from approved requisition -->
                        <div x-show="requisitionPreview" class="mt-3">
                            <label class="block text-sm font-medium text-gray-700 mb-2">{{ $purchaseOrder['link_requisition']['vendors_from_requisition'] }}</label>
                            <div class="bg-gray-50 border border-gray-200 rounded-md p-3 text-sm text-gray-800">
                                <template x-for="group in requisitionPreview.groups" :key="group.vendor_name">
                                    <div class="flex justify-between py-1 border-b last:border-0 border-gray-200">
                                        <span x-text="group.vendor_name"></span>
                                        <span class="text-gray-600" x-text="'{{ $purchaseOrder['link_requisition']['vendors_summary'] }}'.replace(':items', group.item_count).replace(':quantity', group.total_quantity.toFixed(2))"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Order Items (disabled for manual editing) -->
                    <div class="mb-4">
                        <div class="flex items-center justify-between mb-2">
                            <label class="block text-sm font-medium text-gray-700">
                                {{ $purchaseOrder['order_items']['heading'] }}
                            </label>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 text-sm rounded-md p-3">
                            {!! nl2br(e($purchaseOrder['order_items']['locked_notice'])) !!}
                        </div>
                    </div>

                    <!-- Requisition Vendor Grouping Preview -->
                    <template x-if="requisitionPreview">
                        <div class="bg-white shadow-md rounded-lg overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                                <div>
                                    @php
                                        $previewTitle = str_replace(':id', '<span x-text="requisitionPreview.id"></span>', $purchaseOrder['order_items']['preview']['title']);
                                    @endphp
                                    <h3 class="text-lg font-semibold text-gray-900">{!! $previewTitle !!}</h3>
                                    <p class="text-sm text-gray-600">
                                        {{ $purchaseOrder['order_items']['preview']['chef'] }} <span x-text="requisitionPreview.chef"></span>
                                        •
                                        {{ $purchaseOrder['order_items']['preview']['requested_for'] }} <span x-text="requisitionPreview.requested_for_date"></span>
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 uppercase">{{ $purchaseOrder['order_items']['preview']['subtotal_label'] }}</p>
                                    <p class="text-lg font-bold text-indigo-600" x-text="formatCurrency(requisitionPreview.subtotal)"></p>
                                </div>
                            </div>
                            <div class="p-6 space-y-6">
                                <template x-for="group in requisitionPreview.groups" :key="group.vendor_name">
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <div class="bg-indigo-50 px-4 py-3 border-b border-indigo-100 flex items-center justify-between">
                                            <div>
                                                <h4 class="text-base font-semibold text-gray-900" x-text="group.vendor_name"></h4>
                                                <template x-if="window.vendorContacts && window.vendorContacts[group.vendor_name]">
                                                    <p class="text-xs text-gray-600">
                                                        <span x-text="window.vendorContacts[group.vendor_name].email"></span> •
                                                        <span x-text="window.vendorContacts[group.vendor_name].phone"></span>
                                                    </p>
                                                </template>
                                                <p class="text-xs text-gray-600 mt-1" x-text="'{{ $purchaseOrder['order_items']['preview']['group_summary'] }}'.replace(':items', group.item_count).replace(':quantity', group.total_quantity.toFixed(2))"></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs text-gray-500 uppercase">{{ $purchaseOrder['order_items']['preview']['vendor_subtotal'] }}</p>
                                                <p class="text-lg font-bold text-indigo-900" x-text="formatCurrency(group.vendor_subtotal)"></p>
                                            </div>
                                        </div>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $purchaseOrder['order_items']['preview']['table_headers']['item'] }}</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $purchaseOrder['order_items']['preview']['table_headers']['qty'] }}</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $purchaseOrder['order_items']['preview']['table_headers']['unit'] }}</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $purchaseOrder['order_items']['preview']['table_headers']['unit_price'] }}</th>
                                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ $purchaseOrder['order_items']['preview']['table_headers']['line_total'] }}</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    <template x-for="it in group.items" :key="it.item_id ?? it.item">
                                                        <tr>
                                                            <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="it.item ?? it.item_id"></td>
                                                            <td class="px-4 py-3 text-sm text-gray-900" x-text="(parseFloat(it.quantity||0)).toFixed(2)"></td>
                                                            <td class="px-4 py-3 text-sm text-gray-900" x-text="it.uom ?? it.unit"></td>
                                                            <td class="px-4 py-3 text-sm text-gray-900" x-text="formatCurrency(parseFloat(it.price || 0))"></td>
                                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900" x-text="formatCurrency((parseFloat(it.price || 0) * parseFloat(it.quantity || 0)))"></td>
                                                        </tr>
                                                    </template>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </template>
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                    <div class="grid grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500">{{ $purchaseOrder['order_items']['preview']['totals']['total_items'] }}</p>
                                            <p class="text-lg font-semibold" x-text="requisitionPreview.total_items"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">{{ $purchaseOrder['order_items']['preview']['totals']['total_quantity'] }}</p>
                                            <p class="text-lg font-semibold" x-text="requisitionPreview.total_quantity.toFixed(2)"></p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500">{{ $purchaseOrder['order_items']['preview']['totals']['grand_subtotal'] }}</p>
                                            <p class="text-lg font-bold text-indigo-600" x-text="formatCurrency(requisitionPreview.subtotal)"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Additional Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Expected Delivery Date -->
                        <div>
                            <label for="requested_delivery_date" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $purchaseOrder['delivery']['requested_date'] }}
                            </label>
                            <input type="date" 
                                   name="requested_delivery_date" 
                                   id="requested_delivery_date" 
                                   x-model="requestedDeliveryDate"
                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            @error('requested_delivery_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Payment Terms -->
                        <div>
                            <label for="payment_terms" class="block text-sm font-medium text-gray-700 mb-2">
                                {{ $purchaseOrder['delivery']['payment_terms'] }}
                            </label>
                            <select name="payment_terms" id="payment_terms" 
                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <option value="">{{ $purchaseOrder['delivery']['payment_terms_placeholder'] }}</option>
                                <option value="net_7">{{ $purchaseOrder['delivery']['payment_terms_options']['net_7'] }}</option>
                                <option value="net_15">{{ $purchaseOrder['delivery']['payment_terms_options']['net_15'] }}</option>
                                <option value="net_30">{{ $purchaseOrder['delivery']['payment_terms_options']['net_30'] }}</option>
                                <option value="net_60">{{ $purchaseOrder['delivery']['payment_terms_options']['net_60'] }}</option>
                                <option value="cod">{{ $purchaseOrder['delivery']['payment_terms_options']['cod'] }}</option>
                                <option value="advance">{{ $purchaseOrder['delivery']['payment_terms_options']['advance'] }}</option>
                            </select>
                            @error('payment_terms')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Delivery Instructions -->
                    <div>
                        <label for="delivery_instructions" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $purchaseOrder['instructions']['delivery'] }}
                        </label>
                        <textarea name="delivery_instructions" 
                                  id="delivery_instructions" 
                                  rows="3" 
                                  class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="{{ $purchaseOrder['instructions']['delivery_placeholder'] }}"></textarea>
                        @error('delivery_instructions')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $purchaseOrder['instructions']['notes'] }}
                        </label>
                        <textarea name="notes" 
                                  id="notes" 
                                  rows="3" 
                                  class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="{{ $purchaseOrder['instructions']['notes_placeholder'] }}"></textarea>
                        @error('notes')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Duplicate PO warning -->
                    <template x-if="requisitionPreview && requisitionPreview.purchase_order">
                        <div class="bg-red-50 border border-red-200 text-red-800 text-sm rounded-md p-3">
                            {{ $purchaseOrder['alerts']['duplicate_po'] }}
                        </div>
                    </template>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="send_to_supplier" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">{{ $purchaseOrder['email']['send_to_supplier'] }}</span>
                            </label>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('dashboard') }}" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ $purchaseOrder['actions']['cancel'] }}
                            </a>
                            <button type="submit" name="action" value="draft" 
                                    :disabled="!selectedRequisitionId || (requisitionPreview && requisitionPreview.purchase_order)"
                                    class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                {{ $purchaseOrder['actions']['save_draft'] }}
                            </button>
                            <button type="submit" name="action" value="submit" 
                                    :disabled="!selectedRequisitionId || (requisitionPreview && requisitionPreview.purchase_order)"
                                    class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
                                <span class="flex items-center">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    {{ $purchaseOrder['actions']['submit'] }}
                                </span>
                            </button>
                        </div>
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
                            <strong>{{ $purchaseOrder['info_panel']['tip_prefix'] }}</strong> {{ $purchaseOrder['info_panel']['message'] }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function purchaseOrderForm() {
            return {
                selectedRequisitionId: '{{ $selectedRequisition->id ?? '' }}',
                requisitionPreview: null,
                requestedDeliveryDate: '',
                currencyMeta: window.appCurrency || { code: 'USD', symbol: '$', precision: 2 },
                unknownVendor: @json($purchaseOrder['js']['unknown_vendor']),
                init() {
                    if (this.selectedRequisitionId) {
                        this.loadRequisitionPreview();
                    }
                },
                items: [{ id: 1, description: '', quantity: '', unit: '', unit_price: '', total: 0 }],
                addItem() {
                    this.items.push({ id: this.items.length + 1, description: '', quantity: '', unit: '', unit_price: '', total: 0 });
                },
                removeItem(index) {
                    if (this.items.length > 1) this.items.splice(index, 1);
                },
                calculateTotal(item) {
                    const qty = parseFloat(item.quantity) || 0;
                    const price = parseFloat(item.unit_price) || 0;
                    item.total = (qty * price).toFixed(2);
                    return item.total;
                },
                getGrandTotal() {
                    return this.items.reduce((sum, item) => sum + (parseFloat(item.total) || 0), 0).toFixed(2);
                },
                formatCurrency(amount) {
                    const currency = this.currencyMeta;
                    const precision = Number.isInteger(currency.precision) ? currency.precision : 2;
                    const numericAmount = parseFloat(amount ?? 0) || 0;

                    return `${currency.code} ${numericAmount.toLocaleString('en-US', {
                        minimumFractionDigits: precision,
                        maximumFractionDigits: precision,
                    })}`;
                },
                async loadRequisitionPreview() {
                    if (!this.selectedRequisitionId) {
                        this.requisitionPreview = null;
                        this.items = [{ id: 1, description: '', quantity: '', unit: '', unit_price: '', total: 0 }];
                        this.requestedDeliveryDate = '';
                        return;
                    }
                    const res = await fetch(`/api/chef-requisitions/${this.selectedRequisitionId}`);
                    if (res.ok) {
                        const data = await res.json();
                        // Group and compute
                        const grouped = {};
                        (data.items || []).forEach(it => {
                            const vendor = it.vendor || this.unknownVendor;
                            if (!grouped[vendor]) grouped[vendor] = { vendor_name: vendor, items: [], item_count: 0, total_quantity: 0, vendor_subtotal: 0 };
                            grouped[vendor].items.push(it);
                            grouped[vendor].item_count += 1;
                            grouped[vendor].total_quantity += parseFloat(it.quantity || 0);
                            grouped[vendor].vendor_subtotal += (parseFloat(it.price || 0) * parseFloat(it.quantity || 0));
                        });
                        this.requisitionPreview = {
                            id: data.id,
                            chef: data.chef?.name,
                            requested_for_date: data.requested_for_date,
                            groups: Object.values(grouped),
                            subtotal: (data.items || []).reduce((s,it)=> s + (parseFloat(it.price||0)*parseFloat(it.quantity||0)), 0),
                            total_items: (data.items || []).length,
                            total_quantity: (data.items || []).reduce((s,it)=> s + parseFloat(it.quantity||0), 0),
                            purchase_order: data.purchase_order ?? null,
                        };
                        // Auto-populate items table
                        const mapped = (data.items || []).map((it, idx) => {
                            const qty = parseFloat(it.quantity || 0) || 0;
                            const price = parseFloat(it.price || 0) || 0;
                            return { id: idx + 1, description: it.item ?? (it.item_id ?? ''), quantity: qty, unit: it.uom ?? it.unit ?? '', unit_price: price, total: (qty * price).toFixed(2) };
                        });
                        this.items = mapped.length ? mapped : [{ id: 1, description: '', quantity: '', unit: '', unit_price: '', total: 0 }];
                        // Set requested delivery date from requisition
                        this.requestedDeliveryDate = data.requested_for_date || '';
                    }
                }
            }
        }
    </script>
</body>
</html>
