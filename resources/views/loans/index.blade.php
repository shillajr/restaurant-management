@extends('layouts.app')

@section('title', 'Employee Loans')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Employee Loans</h1>
                <p class="mt-1 text-sm text-gray-600">Track and manage employee loans and advances</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('loans.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700">
                    Issue Loan
                </a>
                <a href="{{ route('payroll.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Payroll
                </a>
            </div>
        </div>

        @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
            {{ session('success') }}
        </div>
        @endif

        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg bg-gradient-to-br from-orange-500 to-red-600 p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Active Loans</p>
                        <p class="mt-2 text-3xl font-bold">{{ $activeLoansCount }}</p>
                    </div>
                    <div class="rounded-full bg-white/20 p-3">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="mt-4 text-sm opacity-90">KES {{ number_format($totalActiveBalance, 2) }} outstanding</p>
            </div>

            <div class="rounded-lg bg-gradient-to-br from-red-500 to-pink-600 p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Outstanding Balance</p>
                        <p class="mt-2 text-3xl font-bold">KES {{ number_format($totalActiveBalance, 2) }}</p>
                    </div>
                    <div class="rounded-full bg-white/20 p-3">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="mt-4 text-sm opacity-90">Across all active loans</p>
            </div>

            <div class="rounded-lg bg-gradient-to-br from-blue-500 to-indigo-600 p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Total Issued</p>
                        <p class="mt-2 text-3xl font-bold">KES {{ number_format($totalIssued, 2) }}</p>
                    </div>
                    <div class="rounded-full bg-white/20 p-3">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="mt-4 text-sm opacity-90">All time</p>
            </div>

            <div class="rounded-lg bg-gradient-to-br from-green-500 to-teal-600 p-6 text-white shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm opacity-90">Total Repaid</p>
                        <p class="mt-2 text-3xl font-bold">KES {{ number_format($totalRepaid, 2) }}</p>
                    </div>
                    <div class="rounded-full bg-white/20 p-3">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="mt-4 text-sm opacity-90">{{ $completedLoansCount }} loans completed</p>
            </div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-4 md:grid-cols-3">
            <a href="{{ route('loans.create') }}" class="group rounded-lg border-2 border-orange-200 bg-white p-6 shadow-sm transition-all hover:border-orange-400">
                <div class="flex items-center">
                    <div class="rounded-lg bg-orange-100 p-3 transition-colors group-hover:bg-orange-200">
                        <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold text-gray-900">Issue New Loan</h3>
                        <p class="text-sm text-gray-600">Create employee advance</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('payroll.index') }}" class="group rounded-lg border-2 border-indigo-200 bg-white p-6 shadow-sm transition-all hover:border-indigo-400">
                <div class="flex items-center">
                    <div class="rounded-lg bg-indigo-100 p-3 transition-colors group-hover:bg-indigo-200">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold text-gray-900">View Payroll</h3>
                        <p class="text-sm text-gray-600">Check deductions</p>
                    </div>
                </div>
            </a>

            <a href="{{ route('employees.salary.index') }}" class="group rounded-lg border-2 border-purple-200 bg-white p-6 shadow-sm transition-all hover:border-purple-400">
                <div class="flex items-center">
                    <div class="rounded-lg bg-purple-100 p-3 transition-colors group-hover:bg-purple-200">
                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="font-semibold text-gray-900">Manage Employees</h3>
                        <p class="text-sm text-gray-600">View all employees</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="mb-6 rounded-lg bg-white p-6 shadow-sm">
            <form method="GET" action="{{ route('loans.index') }}" class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div>
                    <label for="employee_id" class="mb-2 block text-sm font-medium text-gray-700">Employee</label>
                    <select name="employee_id" id="employee_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-transparent focus:ring-2 focus:ring-orange-500">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="status" class="mb-2 block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" id="status" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-transparent focus:ring-2 focus:ring-orange-500">
                        <option value="">All Statuses</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>

                <div>
                    <label for="sort" class="mb-2 block text-sm font-medium text-gray-700">Sort By</label>
                    <select name="sort" id="sort" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:border-transparent focus:ring-2 focus:ring-orange-500">
                        <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest First</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="amount_high" {{ request('sort') == 'amount_high' ? 'selected' : '' }}>Amount (High to Low)</option>
                        <option value="amount_low" {{ request('sort') == 'amount_low' ? 'selected' : '' }}>Amount (Low to High)</option>
                    </select>
                </div>

                <div class="flex items-end gap-2">
                    <button type="submit" class="flex-1 rounded-lg bg-orange-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-orange-700">
                        Apply Filters
                    </button>
                    <a href="{{ route('loans.index') }}" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-300">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        @if($loans->count() > 0)
        <div class="overflow-hidden rounded-lg bg-white shadow">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Purpose</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Issue Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Repayment/Cycle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Balance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach($loans as $loan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-orange-100">
                                    <span class="text-sm font-semibold text-orange-600">
                                        {{ strtoupper(substr($loan->employee->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900">{{ $loan->employee->name }}</div>
                                    <div class="text-sm text-gray-500">{{ $loan->employee->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900">KES {{ number_format($loan->amount, 2) }}</div>
                            <div class="text-xs text-gray-500">Original amount</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="max-w-xs truncate text-sm text-gray-900">{{ $loan->purpose }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            {{ $loan->issue_date->format('d M Y') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">KES {{ number_format($loan->repayment_per_cycle, 2) }}</div>
                            <div class="text-xs text-gray-500">Per month</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold {{ $loan->balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                KES {{ number_format($loan->balance, 2) }}
                            </div>
                            @if($loan->balance > 0)
                                <div class="text-xs text-gray-500">
                                    {{ number_format(($loan->amount - $loan->balance) / $loan->amount * 100, 1) }}% repaid
                                </div>
                            @else
                                <div class="text-xs text-green-600">Fully repaid</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($loan->status === 'active')
                                <span class="inline-flex rounded-full bg-blue-100 px-2 py-1 text-xs font-semibold leading-5 text-blue-800">
                                    Active
                                </span>
                            @elseif($loan->status === 'completed')
                                <span class="inline-flex rounded-full bg-green-100 px-2 py-1 text-xs font-semibold leading-5 text-green-800">
                                    Completed
                                </span>
                            @else
                                <span class="inline-flex rounded-full bg-red-100 px-2 py-1 text-xs font-semibold leading-5 text-red-800">
                                    Cancelled
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('loans.show', $loan->id) }}" class="text-indigo-600 transition-colors hover:text-indigo-900" title="View Details">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                </a>
                                @if($loan->status === 'active')
                                    <form action="{{ route('loans.cancel', $loan->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this loan?');">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="text-red-600 transition-colors hover:text-red-900" title="Cancel Loan">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                {{ $loans->links() }}
            </div>
        </div>
        @else
        <div class="rounded-lg bg-white p-12 text-center shadow-sm">
            <div class="mb-4 inline-flex h-16 w-16 items-center justify-center rounded-full bg-orange-100">
                <svg class="h-8 w-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 class="mb-2 text-lg font-semibold text-gray-900">No Loans Found</h3>
            <p class="mb-6 text-gray-600">
                @if(request()->has('employee_id') || request()->has('status'))
                    No loans match your filter criteria. Try adjusting your filters.
                @else
                    No employee loans have been issued yet. Start by issuing a new loan.
                @endif
            </p>
            <div class="flex items-center justify-center gap-3">
                @if(request()->has('employee_id') || request()->has('status'))
                    <a href="{{ route('loans.index') }}" class="rounded-lg bg-gray-200 px-6 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-300">
                        Clear Filters
                    </a>
                @endif
                <a href="{{ route('loans.create') }}" class="rounded-lg bg-orange-600 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-orange-700">
                    Issue New Loan
                </a>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
