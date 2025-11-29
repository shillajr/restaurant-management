@extends('layouts.app')

@section('title', 'Loan Details')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-3xl font-bold text-gray-900">Loan Details</h1>
                    @if($loan->status === 'active')
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-semibold text-blue-800">
                            Active
                        </span>
                    @elseif($loan->status === 'completed')
                        <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-semibold text-green-800">
                            Completed
                        </span>
                    @else
                        <span class="rounded-full bg-red-100 px-3 py-1 text-sm font-semibold text-red-800">
                            Cancelled
                        </span>
                    @endif
                </div>
                <p class="mt-1 text-sm text-gray-600">Loan ID: #{{ $loan->id }}</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('loans.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Back to Loans
                </a>
                <button onclick="window.print()" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Print
                </button>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Employee Information</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-orange-100">
                                <span class="text-xl font-bold text-orange-600">
                                    {{ strtoupper(substr($loan->employee->name, 0, 2)) }}
                                </span>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">{{ $loan->employee->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $loan->employee->email }}</p>
                                @if($loan->employee->roles->isNotEmpty())
                                    <span class="mt-1 inline-block rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-800">
                                        {{ ucfirst($loan->employee->roles->first()->name) }}
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500">Monthly Salary</p>
                                <p class="font-semibold text-gray-900">{{ currency_format($loan->employee->monthly_salary) }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Total Active Loans</p>
                                <p class="font-semibold text-orange-600">
                                    {{ currency_format($loan->employee->active_loans_sum_balance ?? 0) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Loan Details</h2>
                    </div>
                    <div class="space-y-4 p-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <p class="text-sm text-gray-500">Original Amount</p>
                                <p class="text-2xl font-bold text-gray-900">{{ currency_format($loan->amount) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Current Balance</p>
                                <p class="text-2xl font-bold {{ $loan->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ currency_format($loan->balance) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Repayment Per Cycle</p>
                                <p class="text-lg font-semibold text-orange-600">{{ currency_format($loan->repayment_per_cycle) }}</p>
                                <p class="text-xs text-gray-500">Deducted monthly from payroll</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Total Repaid</p>
                                <p class="text-lg font-semibold text-green-600">{{ currency_format($loan->amount - $loan->balance) }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $loan->amount > 0 ? number_format(($loan->amount - $loan->balance) / $loan->amount * 100, 1) : 0 }}% of original amount
                                </p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Issue Date</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $loan->issue_date->format('d M Y') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Estimated Completion</p>
                                <p class="text-lg font-semibold text-gray-900">
                                    @if($loan->status === 'completed')
                                        Completed
                                    @elseif($loan->status === 'cancelled')
                                        Cancelled
                                    @elseif($loan->repayment_per_cycle > 0)
                                        {{ ceil($loan->balance / $loan->repayment_per_cycle) }} months
                                    @else
                                        N/A
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="border-t border-gray-200 pt-4">
                            <p class="mb-2 text-sm text-gray-500">Purpose</p>
                            <p class="text-gray-900">{{ $loan->purpose }}</p>
                        </div>

                        @if($loan->notes)
                        <div class="border-t border-gray-200 pt-4">
                            <p class="mb-2 text-sm text-gray-500">Notes</p>
                            <p class="text-gray-900">{{ $loan->notes }}</p>
                        </div>
                        @endif

                        @php
                            $repaidPercentage = $loan->amount > 0 ? (($loan->amount - $loan->balance) / $loan->amount * 100) : 0;
                        @endphp
                        <div class="border-t border-gray-200 pt-4">
                            <div class="mb-2 flex items-center justify-between">
                                <p class="text-sm text-gray-500">Repayment Progress</p>
                                <p class="text-sm font-semibold text-gray-900">{{ number_format($repaidPercentage, 1) }}%</p>
                            </div>
                            <div class="h-3 w-full rounded-full bg-gray-200">
                                <div
                                    class="h-3 rounded-full {{ $repaidPercentage >= 100 ? 'bg-green-600' : 'bg-orange-600' }}"
                                    style="width: {{ min($repaidPercentage, 100) }}%"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Repayment History</h2>
                    </div>
                    @if($repayments->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payroll Period</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount Deducted</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Balance After</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                @php $runningBalance = $loan->amount; @endphp
                                @foreach($repayments as $repayment)
                                @php
                                    $runningBalance -= $repayment->amount;
                                @endphp
                                <tr class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {{ $repayment->created_at->format('d M Y') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                        {{ $repayment->payroll_month ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-orange-600">
                                        {{ currency_format(-$repayment->amount) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold {{ $runningBalance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                        {{ currency_format($runningBalance) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ $repayment->reference ?? 'Payroll deduction' }}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="2" class="px-6 py-4 text-sm font-semibold text-gray-900">Total Repaid</td>
                                    <td class="px-6 py-4 text-sm font-bold text-green-600">
                                        {{ currency_format($repayments->sum('amount')) }}
                                    </td>
                                    <td colspan="2" class="px-6 py-4 text-sm font-bold text-gray-900">
                                        Current Balance: {{ currency_format($loan->balance) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="p-12 text-center">
                        <div class="mb-4 inline-flex h-12 w-12 items-center justify-center rounded-full bg-orange-100">
                            <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h3 class="mb-2 text-lg font-semibold text-gray-900">No Repayments Yet</h3>
                        <p class="text-gray-600">
                            No repayments have been recorded for this loan yet. Deductions will appear here when payroll is processed.
                        </p>
                    </div>
                    @endif
                </div>

                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Activity Log</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <div class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-green-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Loan Issued</p>
                                    <p class="text-xs text-gray-500">{{ $loan->created_at->format('d M Y, h:i A') }}</p>
                                    <p class="mt-1 text-xs text-gray-600">
                                        Created by {{ $loan->creator->name ?? 'System' }}
                                    </p>
                                </div>
                            </div>

                            @if($loan->status === 'completed')
                            <div class="flex items-start gap-3">
                                <div class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-blue-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Loan Completed</p>
                                    <p class="text-xs text-gray-500">{{ $loan->updated_at->format('d M Y, h:i A') }}</p>
                                    <p class="mt-1 text-xs text-gray-600">Fully repaid</p>
                                </div>
                            </div>
                            @elseif($loan->status === 'cancelled')
                            <div class="flex items-start gap-3">
                                <div class="mt-2 h-2 w-2 flex-shrink-0 rounded-full bg-red-500"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Loan Cancelled</p>
                                    <p class="text-xs text-gray-500">{{ $loan->updated_at->format('d M Y, h:i A') }}</p>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg bg-gradient-to-br from-orange-500 to-red-600 p-6 text-white shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">Quick Summary</h3>
                    <div class="space-y-3">
                        <div class="border-b border-orange-400 pb-3">
                            <p class="text-sm opacity-90">Original Amount</p>
                            <p class="text-2xl font-bold">{{ currency_format($loan->amount) }}</p>
                        </div>
                        <div class="border-b border-orange-400 pb-3">
                            <p class="text-sm opacity-90">Amount Repaid</p>
                            <p class="text-xl font-bold">{{ currency_format($loan->amount - $loan->balance) }}</p>
                        </div>
                        <div class="border-b border-orange-400 pb-3">
                            <p class="text-sm opacity-90">Outstanding Balance</p>
                            <p class="text-xl font-bold">{{ currency_format($loan->balance) }}</p>
                        </div>
                        <div class="pt-2">
                            <p class="text-sm opacity-90">Monthly Deduction</p>
                            <p class="text-lg font-bold">{{ currency_format($loan->repayment_per_cycle) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Repayment Stats</h3>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Payments:</span>
                            <span class="font-semibold text-gray-900">{{ $repayments->count() }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Last Payment:</span>
                            <span class="font-semibold text-gray-900">
                                {{ $repayments->first() ? $repayments->first()->created_at->format('d M Y') : 'N/A' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Remaining Months:</span>
                            <span class="font-semibold text-gray-900">
                                @if($loan->status === 'completed')
                                    0
                                @elseif($loan->status === 'cancelled')
                                    N/A
                                @elseif($loan->repayment_per_cycle > 0)
                                    {{ ceil($loan->balance / $loan->repayment_per_cycle) }}
                                @else
                                    N/A
                                @endif
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Progress:</span>
                            <span class="font-semibold text-green-600">
                                {{ number_format($repaidPercentage, 1) }}%
                            </span>
                        </div>
                    </div>
                </div>

                @if($loan->status === 'active')
                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Actions</h3>
                    <div class="space-y-3">
                        <form action="{{ route('loans.cancel', $loan->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this loan? This action cannot be undone.');">
                            @csrf
                            @method('PUT')
                            <button type="submit" class="w-full rounded-lg bg-red-600 px-4 py-2 text-white transition-colors hover:bg-red-700">
                                Cancel Loan
                            </button>
                        </form>
                        <p class="text-center text-xs text-gray-500">
                            Cancelling will stop future deductions
                        </p>
                    </div>
                </div>
                @endif

                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Quick Links</h3>
                    <div class="space-y-2">
                        <a href="{{ route('loans.index') }}" class="block rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-200">
                            All Loans
                        </a>
                        <a href="{{ route('loans.create') }}" class="block rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-200">
                            Issue New Loan
                        </a>
                        <a href="{{ route('payroll.index') }}?employee_id={{ $loan->employee_id }}" class="block rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-200">
                            View Employee Payroll
                        </a>
                        <a href="{{ route('employees.salary.edit', $loan->employee_id) }}" class="block rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-200">
                            Edit Employee Salary
                        </a>
                    </div>
                </div>

                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800">How Repayments Work</h4>
                            <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-blue-700">
                                <li>Auto-deducted from payroll</li>
                                <li>Tracked monthly in system</li>
                                <li>Auto-completed when paid</li>
                                <li>History maintained for audit</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        header form,
        header button,
        .no-print {
            display: none;
        }
    }
</style>
@endpush
