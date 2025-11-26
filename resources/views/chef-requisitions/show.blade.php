<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Requisition #{{ $chefRequisition->id }} - Restaurant Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50" x-data="{ showApproveModal: false, showRejectModal: false, showRequestChangesModal: false, rejectionReason: '', changeRequest: '' }">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-gray-900">RMS</span>
                    <span class="ml-4 text-gray-600">Requisition Details</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('chef-requisitions.index') }}" class="text-gray-600 hover:text-gray-900">Back to List</a>
                    <a href="{{ route('dashboard') }}" class="text-gray-600 hover:text-gray-900">Dashboard</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-600 hover:text-gray-900">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                        ];
                        $color = $statusColors[$chefRequisition->status] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full {{ $color }}">
                        {{ ucfirst($chefRequisition->status) }}
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
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                                    @if(isset($chefRequisition->items[0]['vendor']))
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                    @endif
                                    @if(isset($chefRequisition->items[0]['price']))
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($chefRequisition->items as $index => $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $item['item'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['quantity'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['unit'] ?? '-' }}</td>
                                    @if(isset($item['vendor']))
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $item['vendor'] }}</td>
                                    @endif
                                    @if(isset($item['price']))
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        TZS {{ number_format($item['price'], 2) }}
                                        @if(isset($item['originalPrice']) && $item['price'] != $item['originalPrice'])
                                            <span class="ml-1 text-xs text-yellow-600" title="Original: TZS {{ number_format($item['originalPrice'], 2) }}">⚠</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        TZS {{ number_format($item['price'] * $item['quantity'], 2) }}
                                    </td>
                                    @endif
                                </tr>
                                @endforeach
                            </tbody>
                            @if(isset($chefRequisition->items[0]['price']))
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="{{ isset($chefRequisition->items[0]['vendor']) ? 6 : 5 }}" class="px-6 py-4 text-right text-sm font-semibold text-gray-900">
                                        Grand Total:
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                                        @php
                                            $grandTotal = collect($chefRequisition->items)->sum(function($item) {
                                                return ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                                            });
                                        @endphp
                                        TZS {{ number_format($grandTotal, 2) }}
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
                            <span class="text-sm font-medium text-gray-900">{{ count($chefRequisition->items) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-sm text-gray-600">Status:</span>
                            <span class="text-sm font-medium text-gray-900">{{ ucfirst($chefRequisition->status) }}</span>
                        </div>
                        @if(isset($grandTotal))
                        <div class="flex justify-between border-t pt-3">
                            <span class="text-sm font-semibold text-gray-900">Total Cost:</span>
                            <span class="text-sm font-bold text-gray-900">TZS {{ number_format($grandTotal, 2) }}</span>
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
                            @if($chefRequisition->status === 'pending')
                                <button @click="showApproveModal = true"
                                        class="block w-full text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                    ✔ Approve Requisition
                                </button>

                                <button @click="showRejectModal = true"
                                        class="block w-full text-center px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                    ✖ Reject Requisition
                                </button>

                                <button @click="showRequestChangesModal = true"
                                        class="block w-full text-center px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors">
                                    🔄 Request Changes
                                </button>

                                <div class="border-t my-3"></div>
                            @endif
                        @endcan

                        <!-- Creator Actions -->
                        @if($chefRequisition->status === 'pending' && Auth::id() === $chefRequisition->chef_id)
                        <a href="{{ route('chef-requisitions.edit', $chefRequisition->id) }}" 
                           class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            Edit Requisition
                        </a>
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
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center mt-4">Approve Requisition</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500 text-center">
                        Are you sure you want to approve this requisition? This will automatically generate a Purchase Order with all items.
                    </p>
                </div>
                <div class="items-center px-4 py-3">
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
                                Approve
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
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center mt-4">Reject Requisition</h3>
                <div class="mt-2 px-7 py-3">
                    <form method="POST" action="{{ route('chef-requisitions.reject', $chefRequisition->id) }}">
                        @csrf
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Rejection Reason *</label>
                            <textarea name="rejection_reason" 
                                      x-model="rejectionReason"
                                      rows="4" 
                                      required
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                      placeholder="Please provide a reason for rejection..."></textarea>
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" 
                                    @click="showRejectModal = false; rejectionReason = ''"
                                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                                Reject
                            </button>
                        </div>
                    </form>
                </div>
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

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>
