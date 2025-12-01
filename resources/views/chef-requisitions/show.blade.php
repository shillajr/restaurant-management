@extends('layouts.app')

@section('title', 'Requisition #'.$chefRequisition->id)

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10" x-data="{ showApproveModal: false, showRejectModal: false, showRequestChangesModal: false, showGeneratePOModal: false, rejectionReason: '', changeRequest: '' }">
    <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Requisition #{{ $chefRequisition->id }}</h1>
                    <p class="text-gray-600 mt-1">Created on {{ $chefRequisition->created_at->format('M d, Y h:i A') }}</p>
                </div>
                <div>
                    @php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'approved' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            'fulfilled' => 'bg-blue-100 text-blue-800',
                            'changes_requested' => 'bg-yellow-100 text-yellow-800',
                        ];
                        $color = $statusColors[$chefRequisition->status] ?? 'bg-gray-100 text-gray-800';
                        $statusLabel = ucwords(str_replace('_', ' ', $chefRequisition->status));
                        $isChangeRequestedState = $chefRequisition->status === 'changes_requested';
                        $approveButtonLabel = $isChangeRequestedState ? '✔ Approve Request Changes' : '✔ Approve Requisition';
                        $approveModalTitle = $isChangeRequestedState ? 'Approve Request Changes' : 'Approve Requisition';
                        $approveSubmitLabel = $isChangeRequestedState ? 'Approve Changes' : 'Approve';
                        $approveModalMessage = $isChangeRequestedState
                            ? 'This will approve the requested updates and close the change-request cycle.'
                            : 'This will approve the requisition and notify the requester.';
                        $rejectButtonLabel = $isChangeRequestedState ? '✖ Reject Request' : '✖ Reject Requisition';
                        $rejectModalTitle = $isChangeRequestedState ? 'Reject Request' : 'Reject Requisition';
                        $rejectSubmitLabel = $isChangeRequestedState ? 'Reject Request' : 'Reject Requisition';
                        $rejectModalPrompt = $isChangeRequestedState
                            ? 'Please share why the resubmission is being rejected. The requester will see this note.'
                            : 'Please share why the requisition is being rejected. The requester will see this note.';
                    @endphp
                    <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full {{ $color }}">
                        {{ $statusLabel }}
                    </span>
                </div>
            </div>
        </div>

        @if($chefRequisition->status === 'changes_requested' && $chefRequisition->change_request)
            @php
                $changeRequestCopy = __('requisitions.resubmit.change_request');
            @endphp
            <div class="mb-6 bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded-lg">
                <h3 class="text-sm font-semibold">{{ $changeRequestCopy['heading'] ?? 'Changes Requested' }}</h3>
                <p class="mt-2 text-sm leading-relaxed">{{ $chefRequisition->change_request }}</p>
            </div>
        @endif

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
            <!-- Main Details -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Basic Information -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Basic Information</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Chef</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $chefRequisition->chef->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Requested For Date</label>
                            <p class="mt-1 text-sm text-gray-900">{{ \Carbon\Carbon::parse($chefRequisition->requested_for_date)->format('M d, Y') }}</p>
                        </div>
                        @if($chefRequisition->checker)
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Checked By</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $chefRequisition->checker->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-500">Checked At</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $chefRequisition->checked_at ? \Carbon\Carbon::parse($chefRequisition->checked_at)->format('M d, Y h:i A') : '-' }}</p>
                        </div>
                        @endif
                    </div>
                    @if($chefRequisition->note)
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-500">Note</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $chefRequisition->note }}</p>
                    </div>
                    @endif
                </div>

                <!-- Items List -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Items Requested</h2>
                    @php
                        $items = collect($chefRequisition->items ?? []);
                        $hasVendorColumn = $items->contains(function ($row) {
                            return !empty($row['vendor'] ?? null);
                        });
                        $hasPriceColumn = $items->contains(function ($row) {
                            return isset($row['price']) || isset($row['unit_price']) || isset($row['line_total']) || isset($row['total']);
                        });
                        $grandTotal = null;
                        if ($hasPriceColumn) {
                            $grandTotal = $items->sum(function ($row) {
                                $price = $row['price'] ?? $row['unit_price'] ?? null;
                                $quantity = $row['quantity'] ?? $row['qty'] ?? 0;

                                if ($price === null) {
                                    return (float)($row['total'] ?? $row['line_total'] ?? 0);
                                }

                                return (float)$price * (float)$quantity;
                            });
                        }
                    @endphp
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                    @if($hasVendorColumn)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                    @endif
                                    @if($hasPriceColumn)
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($items as $index => $item)
                                @php
                                    $itemName = $item['item'] ?? $item['item_name'] ?? $item['name'] ?? 'Unknown Item';
                                    $quantity = $item['quantity'] ?? $item['qty'] ?? 0;
                                    $unit = $item['unit'] ?? $item['uom'] ?? '-';
                                    $vendor = $item['vendor'] ?? null;
                                    $price = $item['price'] ?? $item['unit_price'] ?? null;
                                    $originalPrice = $item['originalPrice'] ?? $item['defaultPrice'] ?? null;
                                    $lineTotal = $price !== null
                                        ? (float)$price * (float)$quantity
                                        : ($item['total'] ?? $item['line_total'] ?? null);
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $itemName }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ rtrim(rtrim(number_format((float)$quantity, 2, '.', ''), '0'), '.') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $unit }}</td>
                                    @if($hasVendorColumn)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $vendor ?? '-' }}</td>
                                    @endif
                                    @if($hasPriceColumn)
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if($price !== null)
                                            {{ currency_format($price) }}
                                        @else
                                            -
                                        @endif
                                        @if($price !== null && $originalPrice !== null && round($price, 4) !== round($originalPrice, 4))
                                            <span class="ml-1 text-xs text-yellow-600" title="Original: {{ currency_format($originalPrice) }}">⚠</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        @if($lineTotal !== null)
                                            {{ currency_format($lineTotal) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                            @if($hasPriceColumn)
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="{{ $hasVendorColumn ? 6 : 5 }}" class="px-6 py-4 text-right text-sm font-semibold text-gray-900">
                                        Grand Total:
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        {{ currency_format($grandTotal) }}
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>

            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Quick Stats -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Summary</h3>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Total Items:</span>
                            <span class="text-sm font-medium text-gray-900">{{ $items->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="text-sm font-medium text-gray-900">{{ $statusLabel }}</span>
                        </div>
                        @if(!is_null($grandTotal))
                        <div class="flex justify-between border-t pt-3">
                            <span class="text-sm font-semibold text-gray-900">Total Cost:</span>
                            <span class="text-sm font-bold text-gray-900">{{ currency_format($grandTotal) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Actions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                    <div class="space-y-3">
                        <!-- Approval Actions (for authorized approvers) -->
                        @can('approve requisitions')
                            @php
                                $canReviewRequisition = in_array($chefRequisition->status, ['pending', 'changes_requested'], true);
                            @endphp

                            @if($canReviewRequisition)
                                <button @click="showApproveModal = true"
                                        class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    {{ $approveButtonLabel }}
                                </button>

                                <button @click="showRejectModal = true"
                                        class="block w-full text-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    {{ $rejectButtonLabel }}
                                </button>

                                @if(!$isChangeRequestedState)
                                <button @click="showRequestChangesModal = true"
                                        class="block w-full text-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                                    🔄 Request Changes
                                </button>
                                @endif

                                <div class="border-t my-3"></div>
                            @endif
                        @endcan

                        <!-- Creator Actions -->
                        @if(($chefRequisition->status === 'pending' || $chefRequisition->status === 'changes_requested') && Auth::id() === $chefRequisition->chef_id)
                        <a href="{{ route('chef-requisitions.edit', $chefRequisition->id) }}" 
                           class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            {{ $chefRequisition->status === 'changes_requested' ? 'Edit & Resubmit' : 'Edit Requisition' }}
                        </a>
                        @endif

                        <!-- Convert to PO Action (for approved requisitions) -->
                        @if($chefRequisition->status === 'approved')
                            @if(!$chefRequisition->purchaseOrder)
                                @if($canGeneratePurchaseOrder)
                                <form method="POST" action="{{ route('purchase-orders.store') }}"
                                      onsubmit="return confirm('Create a Purchase Order from this requisition?');">
                                    @csrf
                                    <input type="hidden" name="requisition_id" value="{{ $chefRequisition->id }}">

                                    <div class="mb-3 text-left">
                                        <label for="assigned_to" class="block text-sm font-medium text-gray-700">Assign to purchaser</label>
                                        @if($purchaserOptions->isNotEmpty())
                                            <select id="assigned_to" name="assigned_to"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                                @foreach($purchaserOptions->sortBy('name') as $purchaser)
                                                    <option value="{{ $purchaser['id'] }}" @selected(old('assigned_to', $defaultPurchaserId) == $purchaser['id'])>
                                                        {{ $purchaser['name'] }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="hidden" name="assigned_to" value="{{ $defaultPurchaserId }}">
                                            <p class="mt-1 text-sm text-gray-500">No dedicated purchasers found. This PO will be assigned to you.</p>
                                        @endif
                                        @error('assigned_to')
                                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button type="submit"
                                            class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                        📋 Generate Purchase Order
                                    </button>
                                </form>
                                @else
                                <button type="button" disabled
                                        class="block w-full text-center px-4 py-2 bg-green-200 text-green-800 rounded-lg cursor-not-allowed"
                                        title="Only approvers can generate purchase orders.">
                                    📋 Generate Purchase Order
                                </button>
                                @endif
                            @else
                            <a href="{{ route('purchase-orders.show', $chefRequisition->purchaseOrder->id) }}"
                               class="block w-full text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                👁 View Purchase Order
                            </a>
                            @endif
                        @endif

                        <a href="{{ route('chef-requisitions.index') }}" 
                           class="block w-full text-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Back to List
                        </a>

                                                @if($chefRequisition->status === 'pending' && Auth::id() === $chefRequisition->chef_id)
                        <form method="POST" action="{{ route('chef-requisitions.destroy', $chefRequisition->id) }}" 
                              onsubmit="return confirm('Are you sure you want to delete this requisition?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="block w-full text-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                Delete Requisition
                            </button>
                        </form>
                        @endif
                    </div>
                </div>

                <!-- Timeline -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Timeline</h3>
                    <div class="space-y-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2a8 8 0 100 16 8 8 0 000-16zM9 9a1 1 0 011-1h.01a1 1 0 110 2H10a1 1 0 01-1-1zm1 4a1 1 0 100-2 1 1 0 000 2z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">Created</p>
                                <p class="text-xs text-gray-500">{{ $chefRequisition->created_at->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>

                        @if($chefRequisition->checked_at)
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 {{ $chefRequisition->status === 'approved' ? 'bg-green-100' : 'bg-red-100' }} rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 {{ $chefRequisition->status === 'approved' ? 'text-green-600' : 'text-red-600' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ ucfirst($chefRequisition->status) }}</p>
                                <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($chefRequisition->checked_at)->format('M d, Y h:i A') }}</p>
                            </div>
                        </div>
                        @endif

                        @if($chefRequisition->change_request && $chefRequisition->status === 'changes_requested')
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-900">{{ __('requisitions.resubmit.change_request.heading') }}</p>
                                <p class="text-xs text-gray-500">{{ $chefRequisition->checked_at ? \Carbon\Carbon::parse($chefRequisition->checked_at)->format('M d, Y h:i A') : '' }}</p>
                                <p class="mt-2 text-sm text-gray-700">{{ $chefRequisition->change_request }}</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approve Modal -->
    <div x-show="showApproveModal" 
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showApproveModal = false">
        <div class="relative top-20 mx-auto w-full max-w-md p-6 border shadow-lg rounded-md bg-white">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="mt-4 text-lg leading-6 font-medium text-gray-900 text-center">{{ $approveModalTitle }}</h3>
            <p class="mt-2 text-sm text-gray-500 text-center">{{ $approveModalMessage }}</p>
            <div class="mt-6">
                <form method="POST" action="{{ route('chef-requisitions.approve', $chefRequisition->id) }}">
                    @csrf
                    <div class="flex space-x-3">
                        <button type="button" 
                                @click="showApproveModal = false"
                                class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                            {{ $approveSubmitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Generate PO Modal -->
    <div x-show="showGeneratePOModal" 
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showGeneratePOModal = false">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center mt-4">Generate Purchase Order</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 text-center">
                        This will create a Purchase Order from this approved requisition. Are you sure you want to continue?
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <form method="POST" action="{{ route('purchase-orders.store') }}" 
                          @submit="console.log('Form submitting...', $event.target.action)">
                        @csrf
                        <input type="hidden" name="requisition_id" value="{{ $chefRequisition->id }}">
                        <div class="flex space-x-3">
                            <button type="button" 
                                    @click="showGeneratePOModal = false"
                                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                                Generate PO
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div x-show="showRejectModal" 
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showRejectModal = false">
        <div class="relative top-20 mx-auto w-full max-w-md p-6 border shadow-lg rounded-md bg-white">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h3 class="mt-4 text-lg leading-6 font-medium text-gray-900 text-center">{{ $rejectModalTitle }}</h3>
            <p class="mt-2 text-sm text-gray-500 text-center">{{ $rejectModalPrompt }}</p>
            <div class="mt-6">
                <form method="POST" action="{{ route('chef-requisitions.reject', $chefRequisition->id) }}">
                    @csrf
                    <div class="mb-4 text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                        <textarea name="rejection_reason" 
                                  x-model="rejectionReason"
                                  rows="4" 
                                  required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                  placeholder="Share the reason for rejection"></textarea>
                    </div>
                    <div class="flex space-x-3">
                        <button type="button" 
                                @click="showRejectModal = false; rejectionReason = ''"
                                class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                            {{ $rejectSubmitLabel }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Request Changes Modal -->
    <div x-show="showRequestChangesModal" 
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showRequestChangesModal = false">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center mt-4">Request Changes</h3>
                <div class="mt-2 px-7 py-3">
                    <form method="POST" action="{{ route('chef-requisitions.request-changes', $chefRequisition->id) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Change Request *</label>
                            <textarea name="change_request" 
                                      x-model="changeRequest"
                                      rows="4" 
                                      required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-transparent"
                                      placeholder="Please describe the required changes..."></textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" 
                                    @click="showRequestChangesModal = false; changeRequest = ''"
                                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700">
                                Send Request
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
</style>
@endpush

@push('scripts')
<script>
    // Debug CSRF token
    document.addEventListener('DOMContentLoaded', function() {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        console.log('CSRF Token from meta:', csrfToken);
        console.log('CSRF Token in form:', document.querySelector('input[name="_token"]')?.value);
        console.log('Current user auth status - Check blade:', '{{ auth()->check() ? "Authenticated as ".auth()->user()->name : "Not authenticated" }}');
        console.log('Session ID:', '{{ session()->getId() }}');
    });
</script>
@endpush
</div>
@endsection
