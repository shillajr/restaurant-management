@extends('layouts.app')

@section('title', 'Payroll Preview')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10" x-data="{ selectedMonth: '{{ $month }}' }">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Payroll Preview</h1>
                <p class="mt-1 text-sm text-gray-600">Preview payroll calculations before creating records</p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('payroll.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Back to Payroll
                </a>
                <a href="{{ route('payroll.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700">
                    Create Payroll
                </a>
            </div>
        </div>

        <div class="mb-6 rounded-lg bg-white shadow">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Select Preview Month</h2>
            </div>
            <div class="p-6">
                <form method="GET" action="{{ route('payroll.preview') }}" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div class="flex-1 sm:max-w-md">
                        <label for="month" class="mb-2 block text-sm font-medium text-gray-700">
                            Payroll Month
                        </label>
                        <input
                            type="month"
                            id="month"
                            name="month"
                            x-model="selectedMonth"
                            class="block w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-transparent focus:ring-2 focus:ring-indigo-500"
                        >
                    </div>
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-indigo-700"
                    >
                        Preview
                    </button>
                </form>
            </div>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-6 md:grid-cols-4">
            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Employees</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900">{{ count($summary['employees']) }}</p>
                    </div>
                    <div class="rounded-full bg-indigo-100 p-3">
                        <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Salaries</p>
                        <p class="mt-2 text-3xl font-bold text-blue-900">KES {{ number_format($summary['totals']['monthly_salary'], 2) }}</p>
                    </div>
                    <div class="rounded-full bg-blue-100 p-3">
                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Previous Debts</p>
                        <p class="mt-2 text-3xl font-bold text-red-900">KES {{ number_format($summary['totals']['previous_debt'], 2) }}</p>
                    </div>
                    <div class="rounded-full bg-red-100 p-3">
                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-6 shadow">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Expected Total</p>
                        <p class="mt-2 text-3xl font-bold text-green-900">KES {{ number_format($summary['totals']['total_due'], 2) }}</p>
                    </div>
                    <div class="rounded-full bg-green-100 p-3">
                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="mb-6 rounded-lg border border-blue-200 bg-blue-50 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h4 class="text-sm font-medium text-blue-800">Preview Notice</h4>
                    <p class="mt-1 text-sm text-blue-700">
                        This is a preview calculation for <strong>{{ $summary['month'] }}</strong>.
                        Absent days are assumed to be 0. Actual payroll creation requires entering absent days and loan deductions for each employee.
                    </p>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-gray-900">Employee Payroll Breakdown</h2>
                <div class="text-sm text-gray-600">
                    {{ count($summary['employees']) }} employee(s)
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">#</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Monthly Salary</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Daily Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Absent Days</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Deduction</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Base Payable</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Previous Debt</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Pending Loans</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total Due</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($summary['employees'] as $index => $employee)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ $index + 1 }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                    {{ $employee['name'] }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    KES {{ number_format($employee['monthly_salary'], 2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                    KES {{ number_format($employee['daily_rate'], 2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                    {{ $employee['absent_days'] }} days
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-red-600">
                                    -KES {{ number_format($employee['absent_days_deduction'], 2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-blue-600">
                                    KES {{ number_format($employee['base_salary_payable'], 2) }}
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-orange-600">
                                    @if($employee['previous_debt'] > 0)
                                        +KES {{ number_format($employee['previous_debt'], 2) }}
                                    @else
                                        <span class="text-gray-400">None</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                    @if($employee['pending_loans'] > 0)
                                        KES {{ number_format($employee['pending_loans'], 2) }}
                                    @else
                                        <span class="text-gray-400">None</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-green-600">
                                    KES {{ number_format($employee['total_due'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-50">
                        <tr class="font-semibold">
                            <td colspan="2" class="px-6 py-4 text-sm text-gray-900">Totals:</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                KES {{ number_format($summary['totals']['monthly_salary'], 2) }}
                            </td>
                            <td class="px-6 py-4"></td>
                            <td class="px-6 py-4"></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-red-600">
                                -KES {{ number_format($summary['totals']['absent_days_deduction'], 2) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-blue-600">
                                KES {{ number_format($summary['totals']['base_salary_payable'], 2) }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-orange-600">
                                +KES {{ number_format($summary['totals']['previous_debt'], 2) }}
                            </td>
                            <td class="px-6 py-4"></td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-bold text-green-600">
                                KES {{ number_format($summary['totals']['total_due'], 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="mt-8 rounded-lg bg-white p-6 shadow">
            <h3 class="mb-4 text-lg font-semibold text-gray-900">Calculation Breakdown</h3>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="space-y-3">
                    <h4 class="text-sm font-semibold uppercase text-gray-700">Formula</h4>
                    <div class="rounded-lg bg-gray-50 p-4">
                        <ul class="space-y-2 text-sm text-gray-700">
                            <li><strong>Daily Rate:</strong> Monthly Salary ÷ 30</li>
                            <li><strong>Absent Deduction:</strong> Absent Days × Daily Rate</li>
                            <li><strong>Base Payable:</strong> Monthly Salary - Absent Deduction</li>
                            <li><strong>Total Due:</strong> Base Payable + Previous Debt - Loan Deductions</li>
                        </ul>
                    </div>
                </div>

                <div class="space-y-3">
                    <h4 class="text-sm font-semibold uppercase text-gray-700">Notes</h4>
                    <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                        <ul class="space-y-2 text-sm text-yellow-800">
                            <li>• This preview assumes zero absent days for all employees</li>
                            <li>• Previous debts are carried forward from prior months</li>
                            <li>• Pending loans show total active loan balances</li>
                            <li>• Actual payroll requires entering absent days and loan deductions</li>
                            <li>• All calculations are done automatically when creating payroll</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
            <a href="{{ route('payroll.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                Back to Payroll
            </a>
            <a href="{{ route('payroll.create') }}?month={{ $month }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-indigo-700">
                Proceed to Create Payroll
            </a>
        </div>
    </div>
</div>
@endsection
