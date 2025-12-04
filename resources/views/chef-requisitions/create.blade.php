@extends('layouts.app')

@php
    $requisition = __('requisitions.create');
    $common = __('common');
@endphp

@section('title', $requisition['title'])

@php
    $availableItems = $availableItems ?? [];
@endphp

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-10">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900">{{ $requisition['title'] }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ $requisition['description'] }}</p>
            </div>

            <!-- Form -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                            <form action="{{ route('chef-requisitions.store') }}" method="POST" 
                                x-data="requisitionForm()"
                                x-init='loadInitial(@json(old('items', [])))'
                      @submit.prevent="submitForm($event)"
                      class="p-6 space-y-6">
                    @csrf

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

                    <!-- Requested For Date -->
                    <div>
                        <label for="requested_for_date" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $requisition['requested_for_date'] }} <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               id="requested_for_date" 
                               name="requested_for_date"
                               value="{{ old('requested_for_date') }}"
                               min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                               required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>

                    <!-- Items Section -->
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">
                                {{ $requisition['items_section']['heading'] }} <span class="text-red-500">*</span>
                            </label>
                            <span class="text-xs text-gray-500">{{ $requisition['items_section']['instructions'] }}</span>
                        </div>

                        @if (empty($availableItems))
                            <div class="mb-4 rounded-md border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                                {{ __('No active items available. Add items to the catalog before creating a requisition.') }}
                            </div>
                        @endif

                        <!-- Items Table -->
                        <div class="overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">{{ $requisition['items_section']['table']['item'] }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">{{ $requisition['items_section']['table']['vendor'] }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/8">{{ str_replace(':currency', currency_label(), $requisition['items_section']['table']['price']) }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/8">{{ $requisition['items_section']['table']['quantity'] }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/12">{{ $requisition['items_section']['table']['unit'] }}</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/8">{{ $requisition['items_section']['table']['line_total'] }}</th>
                                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider w-16">{{ $requisition['items_section']['table']['action'] }}</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <template x-for="(row, index) in items" :key="index">
                                        <tr :class="row.priceEdited ? 'bg-yellow-50' : ''">
                                            <!-- Item Dropdown -->
                                            <td class="px-4 py-3">
                                                <select :name="'items[' + index + '][item_id]'"
                                                        x-model="row.item_id"
                                                        @change="selectItem(index)"
                                                        required
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                                    <option value="">{{ $requisition['items_section']['table']['select_placeholder'] }}</option>
                                                    <template x-for="category in Object.keys(groupedItems)" :key="category">
                                                        <optgroup :label="category">
                                                            <template x-for="item in groupedItems[category]" :key="item.id">
                                                                <option :value="item.id" x-text="item.name + ' (' + item.uom + ')'"></option>
                                                            </template>
                                                        </optgroup>
                                                    </template>
                                                </select>
                                                <span x-show="row.priceEdited" class="inline-flex items-center text-xs text-yellow-700 mt-1">
                                                    <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    {{ $common['messages']['price_modified'] }}
                                                </span>
                                            </td>

                                            <!-- Vendor (Auto-filled, Read-only) -->
                                            <td class="px-4 py-3">
                                                <input type="text"
                                                       :value="row.vendor"
                                                       readonly
                                                       class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50 text-sm text-gray-600 cursor-not-allowed">
                                                <input type="hidden" :name="'items[' + index + '][vendor]'" :value="row.vendor">
                                            </td>

                                            <!-- Price (Editable with tracking) -->
                                            <td class="px-4 py-3">
                                                <div class="relative">
                                                    <input type="number"
                                                           :name="'items[' + index + '][price]'"
                                                           x-model="row.price"
                                                           @input="updateLineTotal(index)"
                                                           @change="trackPriceChange(index)"
                                                           step="0.01"
                                                           min="0"
                                                           required
                                                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                                                           :class="row.priceEdited ? 'border-yellow-400 ring-1 ring-yellow-400' : ''">
                                                    <span x-show="row.defaultPrice && row.price != row.defaultPrice" 
                                                          class="absolute -top-2 -right-2 bg-yellow-400 text-yellow-900 text-xs px-1.5 py-0.5 rounded-full font-medium">
                                                        !
                                                    </span>
                                                </div>
                                                <input type="hidden" :name="'items[' + index + '][default_price]'" :value="row.defaultPrice">
                                                <input type="hidden" :name="'items[' + index + '][price_edited]'" :value="row.priceEdited ? '1' : '0'">
                                                <span x-show="row.defaultPrice && row.price != row.defaultPrice" 
                                                      class="text-xs text-gray-500 mt-1 block">
                                                    {{ $requisition['summary']['was'] }} <span x-text="formatCurrency(row.defaultPrice)"></span>
                                                </span>
                                            </td>

                                            <!-- Quantity -->
                                            <td class="px-4 py-3">
                                                <input type="number"
                                                       :name="'items[' + index + '][quantity]'"
                                                       x-model="row.quantity"
                                                       @input="updateLineTotal(index)"
                                                       step="0.01"
                                                       min="0.01"
                                                       required
                                                       placeholder="0.00"
                                                       class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                                            </td>

                                            <!-- UoM (Auto-filled, Read-only) -->
                                            <td class="px-4 py-3">
                                                <input type="text"
                                                       :value="row.uom"
                                                       readonly
                                                       class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50 text-sm text-gray-600 cursor-not-allowed text-center">
                                                <input type="hidden" :name="'items[' + index + '][uom]'" :value="row.uom">
                                            </td>

                                            <!-- Line Total (Auto-calculated) -->
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-900 text-sm" x-text="formatCurrency(row.lineTotal)"></div>
                                            </td>

                                            <!-- Delete Button -->
                                            <td class="px-4 py-3 text-center">
                                                <button type="button"
                                                        @click="removeItem(index)"
                                                        x-show="items.length > 1"
                                                        class="p-2 text-red-600 hover:bg-red-50 rounded-md transition-colors"
                                                        title="{{ $requisition['items_section']['remove_tooltip'] }}">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <!-- Add Another Item Button -->
                        <div class="mt-4">
                            <button type="button"
                                    @click="addItem()"
                                    class="w-full md:w-auto px-4 py-2 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-md hover:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors flex items-center justify-center gap-2">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                {{ $requisition['items_section']['add_button'] }}
                            </button>
                        </div>
                    </div>

                    <!-- Summary Panel -->
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">{{ $requisition['summary']['heading'] }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Left Column -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">{{ $requisition['summary']['total_items'] }}</span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="items.length"></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">{{ $requisition['summary']['total_quantity'] }}</span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="totalQuantity.toFixed(2)"></span>
                                </div>
                                <div class="flex justify-between items-center py-2">
                                    <span class="text-sm text-gray-600">{{ $requisition['summary']['modified_prices'] }}</span>
                                    <span class="text-sm font-semibold" 
                                          :class="modifiedPricesCount > 0 ? 'text-yellow-600' : 'text-gray-900'" 
                                          x-text="modifiedPricesCount"></span>
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="space-y-3">
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">{{ $requisition['summary']['subtotal'] }}</span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(subtotal)"></span>
                                </div>
                                <div class="flex justify-between items-center py-2 border-b border-gray-200">
                                    <span class="text-sm text-gray-600">{{ $requisition['summary']['taxes'] }}</span>
                                    <span class="text-sm font-semibold text-gray-900" x-text="formatCurrency(0)"></span>
                                </div>
                                <div class="flex justify-between items-center py-3 bg-indigo-50 rounded-lg px-4 -mx-4">
                                    <span class="text-base font-bold text-gray-900">{{ $requisition['summary']['grand_total'] }}</span>
                                    <span class="text-lg font-bold text-indigo-600" x-text="formatCurrency(grandTotal)"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Price Changes Summary -->
                        <div x-show="modifiedPricesCount > 0" class="mt-4 pt-4 border-t border-gray-200">
                            <h4 class="text-sm font-semibold text-yellow-800 mb-2 flex items-center">
                                <svg class="h-4 w-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                                {{ $common['messages']['price_changes_detected'] }}
                            </h4>
                            <div class="bg-yellow-50 rounded-md p-3 space-y-1">
                                <template x-for="(row, index) in items" :key="index">
                                    <div x-show="row.priceEdited" class="text-xs text-yellow-900">
                                        <span x-text="getItemName(row.item_id)"></span>: 
                                        <span class="font-medium" x-text="formatCurrency(row.defaultPrice)"></span> → 
                                        <span class="font-bold" x-text="formatCurrency(row.price)"></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Note -->
                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700 mb-2">
                            {{ $requisition['notes']['label'] }}
                        </label>
                        <textarea id="note" 
                                  name="note"
                                  rows="4"
                                  placeholder="{{ $requisition['notes']['placeholder'] }}"
                                  class="w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">{{ old('note') }}</textarea>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('chef-requisitions.index') }}" 
                           class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                            {{ $requisition['actions']['cancel'] }}
                        </a>
                        <button type="submit"
                                :disabled="items.length === 0 || !isFormValid()"
                                class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                            {{ $requisition['actions']['submit'] }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- Info Card -->
            <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">{{ $requisition['tips']['heading'] }}</h3>
                        <ul class="mt-2 text-sm text-blue-700 list-disc list-inside space-y-1">
                            @foreach ($requisition['tips']['list'] as $tip)
                                <li>{{ $tip }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        window.requisitionFormConfig = window.requisitionFormConfig || {};
        window.requisitionFormConfig.availableItems = @json($availableItems);
    </script>
    @include('chef-requisitions.partials.form-script')
@endpush
