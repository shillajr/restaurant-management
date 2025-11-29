@extends('layouts.app')

@section('title', 'Payroll Details')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Payroll Details</h1>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $payroll->employee->name }} - {{ \Carbon\Carbon::parse($payroll->month)->format('F Y') }}
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                @if($payroll->outstanding_balance > 0)
                    <a href="{{ route('payroll.payment.create', $payroll->id) }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700">
                        Make Payment
                    </a>
                @endif
                <a href="{{ route('payroll.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                    Back to Payroll
                </a>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Employee Information</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Employee Name</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">{{ $payroll->employee->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Email</p>
                                <p class="mt-1 text-lg text-gray-900">{{ $payroll->employee->email }}</p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Payroll Month</p>
                                <p class="mt-1 text-lg font-semibold text-gray-900">
                                    {{ \Carbon\Carbon::parse($payroll->month)->format('F Y') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-500">Payroll Status</p>
                                <p class="mt-1">
                                    @if($payroll->status === 'pending')
                                        <span class="rounded-full bg-yellow-100 px-3 py-1 text-sm font-medium text-yellow-800">Pending</span>
                                    @elseif($payroll->status === 'partial')
                                        <span class="rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800">Partial</span>
                                    @else
                                        <span class="rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">Paid</span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Payroll Breakdown</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Monthly Salary</p>
                                    <p class="text-xs text-gray-500">Base salary for the month</p>
                                </div>
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ currency_format($payroll->monthly_salary) }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between border-b border-gray-100 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Absent Days Deduction</p>
                                    <p class="text-xs text-gray-500">
                                        {{ $payroll->total_absent_days }} days × {{ currency_format($payroll->monthly_salary / 30) }} (daily rate)
                                    </p>
                                </div>
                                <p class="text-lg font-semibold text-red-600">
                                    {{ currency_format(-$payroll->absent_days_deduction) }}
                                </p>
                            </div>

                            <div class="-mx-6 flex items-center justify-between bg-blue-50 px-6 py-3">
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Base Salary Payable</p>
                                    <p class="text-xs text-blue-700">Monthly salary - Absent days deduction</p>
                                </div>
                                <p class="text-lg font-bold text-blue-900">
                                    {{ currency_format($payroll->base_salary_payable) }}
                                </p>
                            </div>

                            @if($payroll->loan_deductions > 0)
                                <div class="flex items-center justify-between border-b border-gray-100 py-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Loan Deductions</p>
                                        <p class="text-xs text-gray-500">Repayment from active loans</p>
                                    </div>
                                    <p class="text-lg font-semibold text-orange-600">
                                        {{ currency_format(-$payroll->loan_deductions) }}
                                    </p>
                                </div>
                            @endif

                            @if($payroll->previous_debt > 0)
                                <div class="flex items-center justify-between border-b border-gray-100 py-3">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">Previous Debt</p>
                                        <p class="text-xs text-gray-500">Outstanding balance from previous months</p>
                                    </div>
                                    <p class="text-lg font-semibold text-red-600">
                                        +{{ currency_format($payroll->previous_debt) }}
                                    </p>
                                </div>
                            @endif

                            <div class="-mx-6 flex items-center justify-between bg-indigo-50 px-6 py-4">
                                <div>
                                    <p class="text-base font-semibold text-indigo-900">Total Due</p>
                                    <p class="text-xs text-indigo-700">Amount to be paid this cycle</p>
                                </div>
                                <p class="text-2xl font-bold text-indigo-900">
                                    {{ currency_format($payroll->total_due) }}
                                </p>
                            </div>

                            <div class="flex items-center justify-between border-b border-gray-100 py-3">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">Total Paid</p>
                                    <p class="text-xs text-gray-500">Sum of all payments made</p>
                                </div>
                                <p class="text-lg font-semibold text-green-600">
                                    {{ currency_format($payroll->total_paid) }}
                                </p>
                            </div>

                            <div class="-mx-6 flex items-center justify-between rounded-b-lg px-6 py-4 {{ $payroll->outstanding_balance > 0 ? 'bg-red-50' : 'bg-green-50' }}">
                                <div>
                                    <p class="text-base font-semibold {{ $payroll->outstanding_balance > 0 ? 'text-red-900' : 'text-green-900' }}">
                                        Outstanding Balance
                                    </p>
                                    <p class="text-xs {{ $payroll->outstanding_balance > 0 ? 'text-red-700' : 'text-green-700' }}">
                                        Remaining amount to be paid
                                    </p>
                                </div>
                                <p class="text-2xl font-bold {{ $payroll->outstanding_balance > 0 ? 'text-red-900' : 'text-green-900' }}">
                                    {{ currency_format($payroll->outstanding_balance) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Payment History</h2>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-800">
                            {{ $payroll->payments->count() }} payment(s)
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        @if($payroll->payments->count() > 0)
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">#</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment Date</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Payment Method</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Processed By</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Notification</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 bg-white">
                                    @foreach($payroll->payments as $index => $payment)
                                        <tr class="hover:bg-gray-50">
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                {{ $index + 1 }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                {{ \Carbon\Carbon::parse($payment->payment_date)->format('d M Y') }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-green-600">
                                                {{ currency_format($payment->amount) }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                {{ $payment->payment_method ?? 'N/A' }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">
                                                {{ $payment->payment_reference ?? 'N/A' }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">
                                                {{ $payment->creator->name ?? 'N/A' }}
                                            </td>
                                            <td class="whitespace-nowrap px-6 py-4 text-sm">
                                                @if($payment->notification_sent)
                                                    <span class="rounded-full bg-green-100 px-2 py-1 text-xs font-medium text-green-800">Sent</span>
                                                @else
                                                    <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800">Not Sent</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50">
                                    <tr>
                                        <td colspan="2" class="px-6 py-4 text-sm font-semibold text-gray-900">Total Payments:</td>
                                        <td class="px-6 py-4 text-sm font-bold text-green-600">
                                            {{ currency_format($payroll->payments->sum('amount')) }}
                                        </td>
                                        <td colspan="4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @else
                            <div class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <p class="mt-4 text-sm font-medium text-gray-900">No payments recorded</p>
                                <p class="mt-1 text-sm text-gray-500">Make a payment to see it listed here</p>
                                @if($payroll->outstanding_balance > 0)
                                    <a href="{{ route('payroll.payment.create', $payroll->id) }}" class="mt-4 inline-flex items-center rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-green-700">
                                        Make First Payment
                                    </a>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                @if($payroll->notes)
                    <div class="rounded-lg bg-white shadow">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">Notes</h2>
                        </div>
                        <div class="p-6">
                            <p class="text-sm text-gray-700">{{ $payroll->notes }}</p>
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-6">
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Loan Summary</h2>
                    </div>
                    <div class="p-6">
                        @if($payroll->employee->loans->count() > 0)
                            <div class="space-y-4">
                                @foreach($payroll->employee->loans as $loan)
                                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <p class="text-sm font-semibold text-gray-900">{{ $loan->loan_type }}</p>
                                                <p class="text-xs text-gray-500">Approved: {{ \Carbon\Carbon::parse($loan->approved_at)->format('d M Y') }}</p>
                                            </div>
                                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                                                {{ ucfirst($loan->status) }}
                                            </span>
                                        </div>
                                        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                            <div>
                                                <dt class="text-gray-500">Principal</dt>
                                                <dd class="font-semibold text-gray-900">{{ currency_format($loan->principal_amount) }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-gray-500">Balance</dt>
                                                <dd class="font-semibold text-gray-900">{{ currency_format($loan->balance) }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-gray-500">Monthly Deduction</dt>
                                                <dd class="font-semibold text-gray-900">{{ currency_format($loan->monthly_deduction) }}</dd>
                                            </div>
                                            <div>
                                                <dt class="text-gray-500">Installments Paid</dt>
                                                <dd class="font-semibold text-gray-900">{{ $loan->installments_paid }} / {{ $loan->installments }}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">No active loans for this employee.</p>
                        @endif
                    </div>
                </div>

                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Other Details</h2>
                    </div>
                    <div class="p-6">
                        <dl class="space-y-4 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Total Absent Days</dt>
                                <dd class="font-semibold text-gray-900">{{ $payroll->total_absent_days }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Bonuses</dt>
                                <dd class="font-semibold text-gray-900">{{ currency_format($payroll->bonuses) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Deductions</dt>
                                <dd class="font-semibold text-gray-900">{{ currency_format($payroll->deductions) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Generated By</dt>
                                <dd class="font-semibold text-gray-900">{{ $payroll->creator->name ?? 'System' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Created At</dt>
                                <dd class="font-semibold text-gray-900">{{ $payroll->created_at->format('d M Y, h:i A') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500">Last Updated</dt>
                                <dd class="font-semibold text-gray-900">{{ $payroll->updated_at->format('d M Y, h:i A') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
