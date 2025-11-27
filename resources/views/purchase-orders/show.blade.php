@extends('layouts.app')

@section('title', 'Purchase Order #'.$purchaseOrder->po_number)

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10" x-data="{ showReturnModal: false, showRejectModal: false, returnReason: '', rejectionReason: '' }">
    <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Purchase Order {{ $purchaseOrder->po_number }}</h1>
                    <p class="text-gray-600 mt-1">Created on {{ $purchaseOrder->created_at->format('M d, Y h:i A') }}</p>
                    @if($purchaseOrder->approved_at)
                    <p class="text-gray-600">Approved on {{ $purchaseOrder->approved_at->format('M d, Y h:i A') }}</p>
                    @endif
                </div>
                <div>
                    @php
                        $workflowColor = \App\Models\PurchaseOrder::workflowStatusColor($purchaseOrder->workflow_status ?? 'pending');
                    @endphp
                    <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full {{ $workflowColor }}">
                        {{ ucfirst(str_replace('_', ' ', $purchaseOrder->workflow_status ?? 'pending')) }}
                    </span>
                </div>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- PO Header Information -->
                <div class="bg-white rounded-lg shadow-sm p-6 no-print">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Purchase Order Details</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">PO Number</label>
                            <p class="mt-1 text-sm font-semibold text-gray-900">{{ $purchaseOrder->po_number }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Linked Requisition</label>
                            <p class="mt-1 text-sm text-gray-900">
                                <a href="{{ route('chef-requisitions.show', $purchaseOrder->requisition_id) }}" 
                                   class="text-indigo-600 hover:text-indigo-800">
                                    Requisition #{{ $purchaseOrder->requisition_id }}
                                </a>
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Created By</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->requisition->chef->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Approved By</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->approver->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Created Date</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->created_at->format('M d, Y') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Approved Date</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->approved_at ? $purchaseOrder->approved_at->format('M d, Y') : 'Pending' }}</p>
                        </div>
                        @if($purchaseOrder->requested_delivery_date)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Requested Delivery Date</label>
                            <p class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($purchaseOrder->requested_delivery_date)->format('M d, Y') }}</p>
                        </div>
                        @endif
                    </div>
                    @if($purchaseOrder->notes)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-500">Notes</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $purchaseOrder->notes }}</p>
                    </div>
                    @endif
                </div>

                <!-- Vendor Sections -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-gray-900">Items by Vendor</h2>
                        <span class="text-sm text-gray-500">{{ $vendorStats['total_vendors'] }} Vendor(s)</span>
                    </div>

                    <div class="space-y-6">
                        @foreach($itemsByVendor as $vendorGroup)
                        <div class="border border-gray-200 rounded-lg overflow-hidden">
                            <!-- Vendor Header -->
                            <div class="bg-indigo-50 px-4 py-3 border-b border-indigo-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-base font-semibold text-gray-900">{{ $vendorGroup['vendor_name'] }}</h3>
                                                        @php($vendorRecord = \App\Models\Vendor::where('name',$vendorGroup['vendor_name'])->first())
                                                        <p class="text-sm text-gray-600 mt-1 print-hide">
                                                            {{ $vendorGroup['item_count'] }} item(s) • Total Qty: {{ number_format($vendorGroup['total_quantity'], 2) }}
                                                            @if($vendorRecord && ($vendorRecord->email || $vendorRecord->phone))
                                                                <br><span class="text-xs text-gray-500">@if($vendorRecord->email) ✉ {{ $vendorRecord->email }} @endif @if($vendorRecord->phone) • ☎ {{ $vendorRecord->phone }} @endif</span>
                                                            @else
                                                                <br><span class="text-xs text-gray-400 italic">No contact info</span>
                                                            @endif
                                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500 uppercase print-hide">Vendor Subtotal</p>
                                        <p class="text-lg font-bold text-indigo-900">TZS {{ number_format($vendorGroup['vendor_subtotal'], 2) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Vendor Items Table -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase print-hide">#</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Quantity</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Unit Price</th>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Line Total</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($vendorGroup['items'] as $index => $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900 print-hide">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['item'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $item['quantity'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $item['unit'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                TZS {{ number_format($item['price'] ?? 0, 2) }}
                                                @if(isset($item['originalPrice']) && $item['price'] != $item['originalPrice'])
                                                    <span class="ml-1 text-xs text-yellow-600 print-hide" title="Original: TZS {{ number_format($item['originalPrice'], 2) }}">⚠</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-sm font-semibold text-gray-900">
                                                TZS {{ number_format(($item['price'] ?? 0) * $item['quantity'], 2) }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot class="bg-indigo-50">
                                        <tr>
                                            <td colspan="5" class="px-4 py-3 text-right text-sm font-semibold text-gray-900">
                                                Vendor Total:
                                            </td>
                                            <td class="px-4 py-3 text-sm font-bold text-indigo-900">
                                                TZS {{ number_format($vendorGroup['vendor_subtotal'], 2) }}
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- PO Total Summary -->
                <div class="bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                    <h2 class="text-xl font-semibold mb-4 print-hide">Purchase Order Summary</h2>
                    <div class="grid grid-cols-2 gap-4 print-hide">
                        <div>
                            <p class="text-indigo-100 text-sm">Total Items</p>
                            <p class="text-2xl font-bold">{{ count($purchaseOrder->items) }}</p>
                        </div>
                        <div>
                            <p class="text-indigo-100 text-sm">Total Quantity</p>
                            <p class="text-2xl font-bold">{{ number_format($purchaseOrder->total_quantity, 2) }}</p>
                        </div>
                        <div>
                            <p class="text-indigo-100 text-sm">Number of Vendors</p>
                            <p class="text-2xl font-bold">{{ $vendorStats['total_vendors'] }}</p>
                        </div>
                        <div>
                            <p class="text-indigo-100 text-sm">Largest Vendor</p>
                            <p class="text-lg font-semibold">{{ $vendorStats['largest_vendor'] ?? 'N/A' }}</p>
                        </div>
                    </div>
                    <div class="mt-6 pt-6 border-t border-indigo-400">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-indigo-100">Subtotal:</span>
                            <span class="text-xl font-semibold">TZS {{ number_format($purchaseOrder->subtotal, 2) }}</span>
                        </div>
                        @if($purchaseOrder->tax > 0)
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-indigo-100">Tax:</span>
                            <span class="text-xl font-semibold">TZS {{ number_format($purchaseOrder->tax, 2) }}</span>
                        </div>
                        @endif
                        @if($purchaseOrder->other_charges > 0)
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-indigo-100">Other Charges:</span>
                            <span class="text-xl font-semibold">TZS {{ number_format($purchaseOrder->other_charges, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between items-center pt-4 mt-4 border-t border-indigo-400">
                            <span class="text-xl font-bold">Grand Total:</span>
                            <span class="text-3xl font-bold">TZS {{ number_format($purchaseOrder->grand_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6 no-print">
                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-6 no-print">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                    <div class="space-y-3">
                        <form method="POST" action="{{ route('purchase-orders.approve', $purchaseOrder->id) }}">
                            @csrf
                            <button type="submit" class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                ✅ Approve PO & Send to Vendors
                            </button>
                        </form>
                        
                        <button @click="showReturnModal = true"
                                class="block w-full text-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                            ↩️ Return for Changes
                        </button>
                        
                        <button @click="showRejectModal = true"
                                class="block w-full text-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            ❌ Reject PO
                        </button>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-sm p-6 no-print">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <button onclick="window.print()" 
                                class="block w-full text-center px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">
                            🖨️ Print PO
                        </button>
                        
                        <a href="{{ route('chef-requisitions.show', $purchaseOrder->requisition_id) }}" 
                           class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            View Requisition
                        </a>
                        
                        <a href="{{ route('dashboard') }}" 
                           class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Vendor Breakdown -->
                <div class="bg-white rounded-lg shadow-sm p-6 no-print">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Vendor Breakdown</h3>
                    <div class="space-y-3">
                        @foreach($itemsByVendor as $vendorGroup)
                        <div class="flex justify-between items-center pb-3 border-b border-gray-200 last:border-0">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $vendorGroup['vendor_name'] }}</p>
                                <p class="text-xs text-gray-500">{{ $vendorGroup['item_count'] }} items</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold text-gray-900">TZS {{ number_format($vendorGroup['vendor_subtotal'], 2) }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ number_format(($vendorGroup['vendor_subtotal'] / $purchaseOrder->subtotal) * 100, 1) }}%
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
                    <div class="space-y-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 9a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm1 4a1 1 0 100-2 1 1 0 000 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Created</p>
                                <p class="text-xs text-gray-500">{{ $purchaseOrder->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>

                        @if($purchaseOrder->approved_at)
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Approved</p>
                                <p class="text-xs text-gray-500">{{ $purchaseOrder->approved_at->format('M d, Y h:i A') }}</p>
                                <p class="text-xs text-gray-500">By {{ $purchaseOrder->approver->name ?? 'System' }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Return for Changes Modal -->
    <div x-show="showReturnModal" 
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showReturnModal = false">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center mt-4">Return PO for Changes</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 text-center mb-4">
                        Please provide a reason for returning this purchase order.
                    </p>
                    <form method="POST" action="{{ route('purchase-orders.return', $purchaseOrder->id) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Return Reason *</label>
                            <textarea name="return_reason" 
                                      x-model="returnReason"
                                      rows="4" 
                                      required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                      placeholder="Explain what needs to be changed..."></textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" 
                                    @click="showReturnModal = false; returnReason = ''"
                                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                                Return PO
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject PO Modal -->
    <div x-show="showRejectModal" 
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showRejectModal = false">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center mt-4">Reject Purchase Order</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 text-center mb-4">
                        Are you sure you want to reject this purchase order? This action cannot be undone.
                    </p>
                    <form method="POST" action="{{ route('purchase-orders.reject', $purchaseOrder->id) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                            <textarea name="rejection_reason" 
                                      x-model="rejectionReason"
                                      rows="4" 
                                      required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                      placeholder="Explain why this PO is being rejected..."></textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" 
                                    @click="showRejectModal = false; rejectionReason = ''"
                                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Reject PO
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    @media print {
        /* Hide elements */
        nav, button, .no-print, .print-hide { display: none !important; }

        /* Clean background and colors */
        body { background: white; color: black; }
        .bg-indigo-50, .bg-gradient-to-r { background: white !important; }
        .text-white, .text-indigo-900, .text-indigo-100 { color: black !important; }

        /* Full width for print content */
        .lg\:col-span-2 {
            grid-column: span 3 / span 3;
            max-width: 100%;
        }

        /* Simplify borders and spacing */
        .shadow-sm, .shadow-lg { box-shadow: none !important; }
        .rounded-lg { border-radius: 0 !important; }
        .border-indigo-100, .border-indigo-400 { border-color: #000 !important; }

        /* Table styling */
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #000; }
        thead th { background: #f0f0f0 !important; font-weight: bold; }
        .bg-indigo-50 { background: #f9f9f9 !important; }

        /* Vendor headers */
        .border-gray-200 { border: 2px solid #000 !important; }

        /* Grand total emphasis */
        .text-3xl { font-size: 24px; font-weight: bold; }

        /* Page breaks */
        .bg-white { page-break-inside: avoid; }

        /* Remove hover effects */
        .hover\:bg-gray-50:hover { background: transparent !important; }
    }
</style>
@endpush
</div>
@endsection
