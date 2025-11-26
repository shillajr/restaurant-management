<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Order #{{ $purchaseOrder->po_number }} - Restaurant Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <!-- Navigation Bar -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <span class="text-xl font-bold text-gray-900">RMS</span>
                    <span class="ml-4 text-gray-600">Purchase Order</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="{{ route('chef-requisitions.show', $purchaseOrder->requisition_id) }}" class="text-gray-600 hover:text-gray-900">View Requisition</a>
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ showUpdateStatusModal: false, newStatus: '{{ $purchaseOrder->status }}' }">
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
                        $statusColors = [
                            'open' => 'bg-blue-100 text-blue-800',
                            'ordered' => 'bg-purple-100 text-purple-800',
                            'partially_received' => 'bg-yellow-100 text-yellow-800',
                            'received' => 'bg-green-100 text-green-800',
                            'closed' => 'bg-gray-100 text-gray-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                        ];
                        $color = $statusColors[$purchaseOrder->status] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <span class="px-4 py-2 inline-flex text-sm leading-5 font-semibold rounded-full {{ $color }}">
                        {{ ucfirst(str_replace('_', ' ', $purchaseOrder->status)) }}
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
                <div class="bg-white rounded-lg shadow-sm p-6">
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
                                        <p class="text-sm text-gray-600 mt-1">
                                            {{ $vendorGroup['item_count'] }} item(s) • 
                                            Total Qty: {{ number_format($vendorGroup['total_quantity'], 2) }}
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-xs text-gray-500 uppercase">Vendor Subtotal</p>
                                        <p class="text-lg font-bold text-indigo-900">TZS {{ number_format($vendorGroup['vendor_subtotal'], 2) }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Vendor Items Table -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
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
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $index + 1 }}</td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item['item'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $item['quantity'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $item['unit'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                TZS {{ number_format($item['price'] ?? 0, 2) }}
                                                @if(isset($item['originalPrice']) && $item['price'] != $item['originalPrice'])
                                                    <span class="ml-1 text-xs text-yellow-600" title="Original: TZS {{ number_format($item['originalPrice'], 2) }}">⚠</span>
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
                    <h2 class="text-xl font-semibold mb-4">Purchase Order Summary</h2>
                    <div class="grid grid-cols-2 gap-4">
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
            <div class="lg:col-span-1 space-y-6">
                <!-- Status Management -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Status Management</h3>
                    <div class="space-y-3">
                        <button @click="showUpdateStatusModal = true"
                                class="block w-full text-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                            Update PO Status
                        </button>
                        
                        <div class="text-sm text-gray-600">
                            <p class="font-medium mb-2">Current Status:</p>
                            <p class="px-3 py-2 {{ $color }} rounded-lg text-center font-medium">
                                {{ ucfirst(str_replace('_', ' ', $purchaseOrder->status)) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow-sm p-6">
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
                <div class="bg-white rounded-lg shadow-sm p-6">
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

    <!-- Update Status Modal -->
    <div x-show="showUpdateStatusModal" 
         x-cloak
         class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50"
         @click.self="showUpdateStatusModal = false">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg leading-6 font-medium text-gray-900 text-center mb-4">Update PO Status</h3>
                <div class="mt-2 px-7 py-3">
                    <form method="POST" action="{{ route('purchase-orders.update-status', $purchaseOrder->id) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Status</label>
                            <select name="status" 
                                    x-model="newStatus"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="open">Open</option>
                                <option value="ordered">Ordered</option>
                                <option value="partially_received">Partially Received</option>
                                <option value="received">Received</option>
                                <option value="closed">Closed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="flex space-x-3">
                            <button type="button" 
                                    @click="showUpdateStatusModal = false"
                                    class="flex-1 px-4 py-2 bg-gray-200 text-gray-900 rounded-lg hover:bg-gray-300">
                                Cancel
                            </button>
                            <button type="submit" 
                                    class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                                Update
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
        @media print {
            nav, .sidebar, button, .no-print { display: none !important; }
            body { background: white; }
        }
    </style>
</body>
</html>
