@extends('layouts.app')

@php
    use Illuminate\Support\Str;
@endphp

@section('title', 'Reports')

@section('content')
    @section('page-title', 'Reports')
    @section('page-actions')
        <a href="{{ route('reports.export', $exportParams) }}"
           class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-200">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5m0 0l5-5m-5 5V4" />
            </svg>
            Export (coming soon)
        </a>
    @endsection

    @include('partials.page-header', [
        'pageDescription' => 'Review sales, expenses, and profit performance across flexible date ranges.',
    ])

    <div class="mx-auto max-w-7xl space-y-8 px-4 py-8 sm:px-6 lg:px-8">
        <section class="rounded-2xl border border-amber-100 bg-white shadow-sm">
            <form method="GET" action="{{ route('reports.index') }}" class="space-y-4 p-6" id="reports-filter-form">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Today's Summary</h2>
                        <p class="mt-1 text-sm text-gray-500">Switch between preset ranges or choose custom dates.</p>
                    </div>
                    <p class="text-sm font-medium text-gray-600">{{ $rangeLabel }}</p>
                </div>

                <div class="flex flex-wrap items-end gap-3">
                    <div @class(['w-full sm:w-auto'])>
                        <label for="range" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Date range</label>
                        <select id="range" name="range" class="w-full rounded-md border-gray-300 px-2.5 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:min-w-[150px]">
                            @foreach($rangeOptions as $key => $option)
                                <option value="{{ $key }}" @selected($selectedRange === $key)>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div id="custom-date-wrapper" @class(['flex flex-wrap items-end gap-3' => true, 'hidden' => $selectedRange !== 'custom'])>
                        <div @class(['w-full sm:w-auto'])>
                            <label for="start_date" class="mb-1 block text-xs font-semibold uppercase text-gray-500">Start date</label>
                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $startDate->toDateString()) }}" class="w-full rounded-md border-gray-300 px-2.5 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:min-w-[140px]">
                        </div>
                        <div @class(['w-full sm:w-auto'])>
                            <label for="end_date" class="mb-1 block text-xs font-semibold uppercase text-gray-500">End date</label>
                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $endDate->toDateString()) }}" class="w-full rounded-md border-gray-300 px-2.5 py-2 text-sm shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-200 sm:min-w-[140px]">
                        </div>
                    </div>
                    <div class="flex items-end gap-3">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-md bg-amber-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-amber-200">
                            Apply filters
                        </button>
                        <a href="{{ route('reports.index') }}" class="text-sm font-medium text-amber-700 hover:text-amber-900">Reset</a>
                    </div>
                </div>
            </form>
        </section>

        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                <p class="font-semibold">Please fix the following:</p>
                <ul class="ml-4 list-disc space-y-1 pt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="space-y-6">
            <h2 class="text-base font-semibold uppercase tracking-wide text-gray-500">Reports Overview</h2>

            <div class="grid grid-cols-1 gap-3 lg:grid-cols-4">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Sales</p>
                    <p class="mt-1 text-xl font-bold text-gray-900">{{ currency_format($summary['total_sales']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $summary['sales_count'] }} {{ Str::plural('transaction', $summary['sales_count']) }}</p>
                    <p class="text-[11px] text-gray-400">{{ currency_format($summary['average_daily_sales']) }} avg / day</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Expenses</p>
                    <p class="mt-1 text-xl font-bold text-red-600">{{ currency_format($summary['total_expenses']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $summary['expense_count'] }} {{ Str::plural('entry', $summary['expense_count']) }}</p>
                    <p class="text-[11px] text-gray-400">{{ currency_format($summary['average_daily_expenses']) }} avg / day</p>
                </div>

                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-700">Net position</p>
                    <p class="mt-1 text-xl font-bold text-amber-700">{{ currency_format($summary['net_position']) }}</p>
                    <p class="mt-1 text-xs text-amber-700">Sales + A/R − Expenses − Credits</p>
                    <p class="text-[11px] text-amber-600">Margin {{ number_format($summary['profit_margin'], 1) }}% over {{ $summary['days'] }} {{ Str::plural('day', $summary['days']) }}</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Net profit</p>
                    <p class="mt-1 text-xl font-bold {{ $summary['net_profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ currency_format($summary['net_profit']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Profitability across range</p>
                    <p class="text-[11px] text-gray-400">Margin {{ number_format($summary['profit_margin'], 1) }}%</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Accounts receivable</p>
                    <p class="mt-1 text-lg font-semibold text-indigo-600">{{ currency_format($summary['accounts_receivable']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Open customer balances</p>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-400">Vendor credits</p>
                    <p class="mt-1 text-lg font-semibold text-orange-600">{{ currency_format($summary['vendor_credits']) }}</p>
                    <p class="mt-1 text-xs text-gray-500">Outstanding supplier obligations</p>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Daily trend</h3>
                    <p class="text-xs uppercase tracking-wide text-gray-400">Sales vs expenses</p>
                </div>
                <div class="mt-4 overflow-hidden rounded-lg border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold text-gray-600">Date</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-600">Sales</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-600">Expenses</th>
                                <th class="px-4 py-2 text-right font-semibold text-gray-600">Profit</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($dailyTrend as $day)
                                <tr>
                                    <td class="px-4 py-2 text-gray-700">{{ $day['date']->translatedFormat('M j, Y') }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-gray-900">{{ currency_format($day['sales']) }}</td>
                                    <td class="px-4 py-2 text-right font-medium text-gray-900">{{ currency_format($day['expenses']) }}</td>
                                    <td class="px-4 py-2 text-right font-semibold {{ $day['profit'] >= 0 ? 'text-green-600' : 'text-red-600' }}">{{ currency_format($day['profit']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($dailyTrend->hasPages())
                        <div class="flex items-center justify-between border-t border-gray-100 bg-white px-4 py-2 text-xs text-gray-500">
                            <p>Showing {{ $dailyTrend->firstItem() }} - {{ $dailyTrend->lastItem() }} of {{ $dailyTrend->total() }}</p>
                            {{ $dailyTrend->links() }}
                        </div>
                    @endif
                    @if($dailyTrend->isEmpty())
                        <p class="px-4 py-6 text-center text-sm text-gray-500">No activity recorded for this range yet.</p>
                    @endif
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900">Top expense categories</h3>
                    <p class="mt-1 text-xs uppercase tracking-wide text-gray-400">Largest spend areas</p>
                    <ul class="mt-4 space-y-3">
                        @forelse($topExpenseCategories as $category)
                            <li class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3">
                                <span class="text-sm font-medium text-gray-700">{{ $category['category'] }}</span>
                                <span class="text-sm font-semibold text-gray-900">{{ currency_format($category['total']) }}</span>
                            </li>
                        @empty
                            <li class="rounded-lg border border-gray-100 px-4 py-5 text-sm text-gray-500">No expense data for this range.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900">Top selling items</h3>
                    <p class="mt-1 text-xs uppercase tracking-wide text-gray-400">Highest revenue contributors</p>
                    <ul class="mt-4 space-y-3">
                        @forelse($topSellingItems as $item)
                            <li class="flex items-center justify-between rounded-lg border border-gray-100 px-4 py-3">
                                <div>
                                    @php
                                        $unitsLabel = Str::plural('unit', abs($item['quantity'] - 1) < 0.01 ? 1 : 2);
                                    @endphp
                                    <p class="text-sm font-medium text-gray-700">{{ $item['name'] }}</p>
                                    <p class="text-xs text-gray-500">{{ number_format($item['quantity'], 2) }} {{ $unitsLabel }}</p>
                                </div>
                                <span class="text-sm font-semibold text-gray-900">{{ currency_format($item['revenue']) }}</span>
                            </li>
                        @empty
                            <li class="rounded-lg border border-gray-100 px-4 py-5 text-sm text-gray-500">No sales records for this range.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
<script>
    (function () {
        const form = document.getElementById('reports-filter-form');
        const rangeSelect = document.getElementById('range');
        const wrapper = document.getElementById('custom-date-wrapper');

        if (!form || !rangeSelect || !wrapper) {
            return;
        }

        rangeSelect.addEventListener('change', function () {
            if (this.value === 'custom') {
                wrapper.classList.remove('hidden');
            } else {
                wrapper.classList.add('hidden');
                form.submit();
            }
        });
    })();
</script>
@endpush
