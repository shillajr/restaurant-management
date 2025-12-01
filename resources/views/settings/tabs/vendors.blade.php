<div
    x-show="productSection === 'vendors'"
    x-cloak
    class="space-y-6"
>
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-semibold text-gray-900">Vendor Directory</h3>
            <p class="mt-1 text-sm text-gray-600">Keep the single source of truth for supplier contacts up to date.</p>
        </div>
        @if($isEditingVendor)
            <a href="{{ route('settings', ['tab' => 'products', 'product_section' => 'vendors']) }}" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M10 3a1 1 0 01.894.553l5 10A1 1 0 0115 15H5a1 1 0 01-.894-1.447l5-10A1 1 0 0110 3z" />
                </svg>
                Exit edit mode
            </a>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-5">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total vendors</p>
                <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $vendorStats['total'] }}</p>
            </div>
            <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-5">
                <p class="text-xs font-medium uppercase tracking-wide text-green-700">Active</p>
                <p class="mt-2 text-2xl font-semibold text-green-800">{{ $vendorStats['active'] }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-5">
                <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Inactive</p>
                <p class="mt-2 text-2xl font-semibold text-amber-800">{{ $vendorStats['inactive'] }}</p>
            </div>
        </div>

        @php
            $vendorFormAction = $isEditingVendor
                ? route('vendors.update', $editingVendor)
                : route('vendors.store');
            $vendorStatusValue = old('vendor_status', $isEditingVendor ? ($editingVendor->is_active ? 'active' : 'inactive') : 'active');
        @endphp

        <div class="space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h4 class="text-base font-semibold text-gray-900">Registered vendors</h4>
                    <p class="mt-1 text-xs text-gray-500">Click edit to update contact details or toggle availability.</p>
                </div>
                <div class="flex items-center gap-2">
                    @if($isEditingVendor)
                        <span class="hidden rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 sm:inline-flex">
                            Editing {{ $editingVendor->name }}
                        </span>
                    @endif
                    <button type="button" @click.prevent="open('showVendorModal')" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                        </svg>
                        {{ $isEditingVendor ? 'Update vendor' : 'Add vendor' }}
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-6 py-3 text-left">Vendor</th>
                            <th class="px-6 py-3 text-left">Contacts</th>
                            <th class="px-6 py-3 text-left">Status</th>
                            <th class="px-6 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($vendors as $vendor)
                            @php
                                $isActiveVendorRow = $isEditingVendor && $editingVendor->id === $vendor->id;
                            @endphp
                            <tr class="{{ $isActiveVendorRow ? 'bg-blue-50/70' : '' }}">
                                <td class="px-6 py-4 align-top">
                                    <div class="font-medium text-gray-900">{{ $vendor->name }}</div>
                                    <div class="mt-1 text-xs text-gray-500">Updated {{ optional($vendor->updated_at)->diffForHumans() ?? 'n/a' }}</div>
                                </td>
                                <td class="px-6 py-4 align-top text-sm text-gray-600">
                                    <div>{{ $vendor->email ?? 'Email not provided' }}</div>
                                    <div class="mt-1">{{ $vendor->phone ?? 'Phone not provided' }}</div>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $vendor->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                        {{ $vendor->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 align-top">
                                    <div class="flex items-center justify-end gap-3 text-sm">
                                        <a href="{{ route('settings', ['tab' => 'products', 'product_section' => 'vendors', 'edit_vendor' => $vendor->id]) }}" class="font-medium text-blue-600 hover:text-blue-700">Edit</a>

                                        @if($vendor->is_active)
                                            <form action="{{ route('vendors.archive', $vendor) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-md border border-gray-300 px-3 py-1 text-xs font-medium text-gray-600 hover:bg-gray-100">Archive</button>
                                            </form>
                                        @else
                                            <form action="{{ route('vendors.restore', $vendor) }}" method="POST" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rounded-md border border-green-300 px-3 py-1 text-xs font-medium text-green-700 hover:bg-green-100">Reactivate</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No vendors available yet. Use the Add vendor button to create your first supplier.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div
            x-cloak
            x-show="showVendorModal"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @keydown.escape.window="close('showVendorModal')"
            class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4 py-6"
            @click.self="close('showVendorModal')"
        >
            <div class="w-full max-w-xl overflow-hidden rounded-lg bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">{{ $isEditingVendor ? 'Update vendor' : 'Add vendor' }}</h3>
                        <p class="mt-1 text-sm text-gray-500">Vendors feed every dropdown across items, requisitions, and POs.</p>
                    </div>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="close('showVendorModal')">
                        <span class="sr-only">Close</span>
                        <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
                <div class="max-h-[80vh] overflow-y-auto px-6 py-6">
                    @if ($errors->vendors->any())
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <ul class="list-disc space-y-1 pl-5">
                                @foreach ($errors->vendors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ $vendorFormAction }}" method="POST" class="space-y-4">
                        @csrf
                        @if ($isEditingVendor)
                            @method('PUT')
                        @endif

                        <div>
                            <label for="vendor-name" class="block text-sm font-medium text-gray-700">Vendor name <span class="text-red-500">*</span></label>
                            <input id="vendor-name" name="vendor_name" type="text" value="{{ old('vendor_name', $editingVendor->name ?? '') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="vendor-email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input id="vendor-email" name="vendor_email" type="email" value="{{ old('vendor_email', $editingVendor->email ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="orders@example.com">
                        </div>

                        <div>
                            <label for="vendor-phone" class="block text-sm font-medium text-gray-700">Phone number</label>
                            <input id="vendor-phone" name="vendor_phone" type="text" value="{{ old('vendor_phone', $editingVendor->phone ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="e.g. +255757022929">
                        </div>

                        <div>
                            <label for="vendor-status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                            <select id="vendor-status" name="vendor_status" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                <option value="active" @selected($vendorStatusValue === 'active')>Active</option>
                                <option value="inactive" @selected($vendorStatusValue === 'inactive')>Inactive</option>
                            </select>
                        </div>

                        <div class="rounded-md border border-blue-100 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                            Changing a vendor to inactive hides it from selection lists going forward but keeps historical records intact.
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-700" @click="close('showVendorModal')">Cancel</button>
                            @if ($isEditingVendor)
                                <a href="{{ route('settings', ['tab' => 'products', 'product_section' => 'vendors']) }}" class="text-sm font-medium text-gray-500 hover:text-gray-700">Exit edit mode</a>
                            @endif
                            <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                {{ $isEditingVendor ? 'Save vendor' : 'Create vendor' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
