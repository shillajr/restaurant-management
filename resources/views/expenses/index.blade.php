@extends('layouts.app')

@section('title', 'Expenses')

@section('content')
    @section('page-title', 'Expenses')
    @section('page-actions')
        <a href="{{ route('expenses.create') }}" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Add Expense
        </a>
    @endsection

    @include('partials.page-header', [
        'pageDescription' => 'Review spending history, narrow your view with filters, and add new expenses without leaving this workspace.',
    ])

    @php
        $activeFilters = collect($filters ?? [])->filter(fn ($value) => !is_null($value) && $value !== '');
    @endphp

    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
        <section class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
            <form method="GET" action="{{ route('expenses.index') }}" class="space-y-6 p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Filter expenses</h2>
                        <p class="mt-1 text-sm text-gray-500">Use quick filters to focus on the transactions you need.</p>
                    </div>
                    @if ($activeFilters->isNotEmpty())
                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-blue-600">
                            {{ $activeFilters->count() }} filter{{ $activeFilters->count() === 1 ? '' : 's' }} applied
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <label for="date_from" class="block text-sm font-medium text-gray-700">Date from</label>
                        <input type="date" name="date_from" id="date_from" value="{{ $filters['date_from'] }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="date_to" class="block text-sm font-medium text-gray-700">Date to</label>
                        <input type="date" name="date_to" id="date_to" value="{{ $filters['date_to'] }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700">Category</label>
                        <select name="category" id="category" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="">All categories</option>
                            @foreach ($categories as $categoryOption)
                                <option value="{{ $categoryOption }}" @selected($filters['category'] === $categoryOption)>{{ $categoryOption }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label for="amount_min" class="block text-sm font-medium text-gray-700">Min amount</label>
                            <input type="number" name="amount_min" id="amount_min" min="0" step="0.01" value="{{ $filters['amount_min'] }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="0.00">
                        </div>
                        <div>
                            <label for="amount_max" class="block text-sm font-medium text-gray-700">Max amount</label>
                            <input type="number" name="amount_max" id="amount_max" min="0" step="0.01" value="{{ $filters['amount_max'] }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="250.00">
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-3">
                    @if ($activeFilters->isNotEmpty())
                        <a href="{{ route('expenses.index') }}" class="inline-flex items-center rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">Reset</a>
                    @endif
                    <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">Apply filters</button>
                </div>
            </form>
        </section>

        <section class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900">Recent expenses</h2>
                    <p class="mt-1 text-sm text-gray-500">Showing {{ $expenses->total() }} record{{ $expenses->total() === 1 ? '' : 's' }}{{ $activeFilters->isNotEmpty() ? ' based on your filters' : '' }}.</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-6 py-3">Date</th>
                                <th class="px-6 py-3">Category</th>
                                <th class="px-6 py-3">Description</th>
                                <th class="px-6 py-3">Vendor</th>
                                <th class="px-6 py-3">Payment</th>
                                <th class="px-6 py-3 text-right">Amount</th>
                                <th class="px-6 py-3 text-right">Recorded by</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($expenses as $expense)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 align-top text-gray-700">
                                        <div class="font-medium text-gray-900">{{ optional($expense->expense_date)->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ optional($expense->created_at)->format('g:i a') }}</div>
                                    </td>
                                    <td class="px-6 py-4 align-top text-gray-700">{{ $expense->category }}</td>
                                    <td class="px-6 py-4 align-top text-gray-700">
                                        <div class="font-medium text-gray-900">{{ $expense->item_name }}</div>
                                        @if ($expense->description)
                                            <p class="mt-1 text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($expense->description, 80) }}</p>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 align-top text-gray-700">{{ $expense->vendor ?? '—' }}</td>
                                    <td class="px-6 py-4 align-top text-gray-700">{{ $expense->payment_method ?? '—' }}</td>
                                    <td class="px-6 py-4 align-top text-right font-semibold text-gray-900">{{ currency_format($expense->amount) }}</td>
                                    <td class="px-6 py-4 align-top text-right text-gray-600">{{ $expense->creator->name ?? 'System' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                                        <div class="flex flex-col items-center gap-3">
                                            <svg class="h-10 w-10 text-gray-300" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 8v4l3 3M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            <p>No expenses match your current filters.</p>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('expenses.index') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Clear filters</a>
                                                <span aria-hidden="true" class="text-gray-300">|</span>
                                                <a href="{{ route('expenses.create') }}" class="text-sm font-medium text-blue-600 hover:text-blue-700">Add your first expense</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($expenses->hasPages())
                    <div class="border-t border-gray-200 bg-gray-50 px-6 py-4">
                        {{ $expenses->onEachSide(1)->links() }}
                    </div>
                @endif
            </div>
        </section>
    </div>
@endsection
