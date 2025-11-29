@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-7xl">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Purchase Orders</h1>
                <p class="text-gray-600 mt-1">View and manage all generated purchase orders.</p>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
        @endif
        
        @if(session('info'))
        <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
            {{ session('info') }}
        </div>
        @endif
        
        @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
            {{ session('error') }}
        </div>
        @endif

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
            <form action="{{ route('purchase-orders.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Search -->
                    <div>
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input type="text" 
                               name="search" 
                               id="search" 
                               value="{{ request('search') }}" 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2" 
                               placeholder="PO# or Req#">
                    </div>
                    
                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" 
                                id="status" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2">
                            <option value="">All Statuses</option>
                            <option value="open" @selected(request('status') == 'open')>Open</option>
                            <option value="assigned" @selected(request('status') == 'assigned')>Assigned</option>
                            <option value="ordered" @selected(request('status') == 'ordered')>Ordered</option>
                            <option value="partially_received" @selected(request('status') == 'partially_received')>Partially Received</option>
                            <option value="received" @selected(request('status') == 'received')>Received</option>
                            <option value="closed" @selected(request('status') == 'closed')>Closed</option>
                            <option value="cancelled" @selected(request('status') == 'cancelled')>Cancelled</option>
                        </select>
                    </div>
                    
                    <!-- Workflow Status Filter -->
                    <div>
                        <label for="workflow_status" class="block text-sm font-medium text-gray-700 mb-1">Workflow Status</label>
                        <select name="workflow_status" 
                                id="workflow_status" 
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2">
                            <option value="">All Workflows</option>
                            <option value="pending" @selected(request('workflow_status') == 'pending')>Pending</option>
                            <option value="sent_to_vendor" @selected(request('workflow_status') == 'sent_to_vendor')>Sent to Vendor</option>
                            <option value="returned" @selected(request('workflow_status') == 'returned')>Returned</option>
                            <option value="approved" @selected(request('workflow_status') == 'approved')>Approved</option>
                            <option value="rejected" @selected(request('workflow_status') == 'rejected')>Rejected</option>
                        </select>
                    </div>
                    
                    <!-- Date From -->
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                        <input type="date" 
                               name="date_from" 
                               id="date_from" 
                               value="{{ request('date_from') }}" 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2">
                    </div>
                    
                    <!-- Date To -->
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                        <input type="date" 
                               name="date_to" 
                               id="date_to" 
                               value="{{ request('date_to') }}" 
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm px-3 py-2">
                    </div>
                </div>
                
                <!-- Filter Actions -->
                <div class="flex items-center justify-between pt-4 border-t">
                    <div class="text-sm text-gray-500">
                        Showing {{ $purchaseOrders->total() }} purchase order(s)
                    </div>
                    <div class="flex space-x-3">
                        <a href="{{ route('purchase-orders.index') }}" 
                           class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Clear Filters
                        </a>
                        <button type="submit" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                            </svg>
                            Apply Filters
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- PO Table -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PO Number</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requisition</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Workflow</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($purchaseOrders as $po)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('purchase-orders.show', $po->id) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-900">
                                    {{ $po->po_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="{{ route('chef-requisitions.show', $po->requisition_id) }}" class="text-sm text-gray-600 hover:text-indigo-600">
                                    Req #{{ $po->requisition_id }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ count($po->items ?? []) }} item(s)</div>
                                <div class="text-xs text-gray-500">{{ $po->total_quantity }} units</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'open' => 'bg-blue-100 text-blue-800',
                                        'assigned' => 'bg-indigo-100 text-indigo-800',
                                        'ordered' => 'bg-purple-100 text-purple-800',
                                        'partially_received' => 'bg-yellow-100 text-yellow-800',
                                        'received' => 'bg-green-100 text-green-800',
                                        'closed' => 'bg-gray-100 text-gray-800',
                                        'cancelled' => 'bg-red-100 text-red-800',
                                    ];
                                    $color = $statusColors[$po->status] ?? 'bg-gray-100 text-gray-800';
                                @endphp
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $color }}">
                                    {{ ucfirst(str_replace('_', ' ', $po->status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ \App\Models\PurchaseOrder::workflowStatusColor($po->workflow_status) }}">
                                    {{ ucfirst(str_replace('_', ' ', $po->workflow_status)) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">{{ currency_format($po->grand_total) }}</div>
                                <div class="text-xs text-gray-500">{{ $po->creator->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $po->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $po->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <a href="{{ route('purchase-orders.show', $po->id) }}" 
                                   class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 transition-colors">
                                    View Details
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-12">
                                <div class="text-gray-500">
                                    <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <h3 class="mt-4 text-sm font-medium text-gray-900">No Purchase Orders Found</h3>
                                    <p class="mt-2 text-sm text-gray-500">
                                        @if(request()->hasAny(['status', 'workflow_status', 'search', 'date_from', 'date_to']))
                                            No purchase orders match your current filters. Try adjusting your search criteria.
                                        @else
                                            Get started by generating a purchase order from an approved requisition.
                                        @endif
                                    </p>
                                    @if(request()->hasAny(['status', 'workflow_status', 'search', 'date_from', 'date_to']))
                                        <div class="mt-4">
                                            <a href="{{ route('purchase-orders.index') }}" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">
                                                Clear all filters →
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($purchaseOrders->hasPages())
            <div class="px-6 py-4 bg-white border-t border-gray-200">
                {{ $purchaseOrders->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
