@extends('layouts.app')

@section('title', 'Issue New Loan')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10" x-data="loanForm()">
    <div class="mx-auto max-w-5xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Issue New Loan</h1>
                <p class="mt-1 text-sm text-gray-600">Create an advance or loan for an employee</p>
            </div>
            <a href="{{ route('loans.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                Back to Loans
            </a>
        </div>

        @if($errors->any())
        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <ul class="list-inside list-disc text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="rounded-lg bg-white shadow">
                    <div class="border-b border-gray-200 px-6 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Loan Details</h2>
                    </div>
                    <form method="POST" action="{{ route('loans.store') }}" class="space-y-6 p-6">
                        @csrf

                        <div>
                            <label for="employee_id" class="mb-2 block text-sm font-medium text-gray-700">
                                Employee <span class="text-red-500">*</span>
                            </label>
                            <select
                                id="employee_id"
                                name="employee_id"
                                x-model="selectedEmployeeId"
                                @change="updateEmployeeInfo()"
                                class="block w-full rounded-lg border border-gray-300 px-4 py-3 text-sm focus:border-transparent focus:ring-2 focus:ring-orange-500"
                                required
                            >
                                <option value="">Select an employee</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}
                                        data-name="{{ $employee->name }}"
                                        data-email="{{ $employee->email }}"
                                        data-salary="{{ $employee->monthly_salary }}"
                                        data-active-loans="{{ $employee->active_loans_sum_balance ?? 0 }}"
                                    >
                                        {{ $employee->name }} - {{ $employee->email }}
                                    </option>
                                @endforeach
                            </select>
                            @error('employee_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div x-show="selectedEmployee" class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                            <h4 class="mb-2 text-sm font-medium text-blue-900">Employee Information</h4>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <p class="text-blue-700">Monthly Salary</p>
                                    <p class="text-lg font-bold text-blue-900" x-text="formatCurrency(selectedEmployee.salary)"></p>
                                </div>
                                <div>
                                    <p class="text-blue-700">Active Loans Balance</p>
                                    <p class="text-lg font-bold text-blue-900" x-text="formatCurrency(selectedEmployee.activeLoans)"></p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label for="amount" class="mb-2 block text-sm font-medium text-gray-700">
                                Loan Amount <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">
                                    {{ currency_label() }}
                                </div>
                                <input
                                    type="number"
                                    id="amount"
                                    name="amount"
                                    x-model="amount"
                                    @input="calculateEstimatedMonths()"
                                    step="0.01"
                                    min="0.01"
                                    value="{{ old('amount') }}"
                                    class="block w-full rounded-lg border border-gray-300 py-3 pl-12 pr-12 text-lg font-semibold focus:border-transparent focus:ring-2 focus:ring-orange-500"
                                    placeholder="0.00"
                                    required
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Enter the total loan amount to be issued</p>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="repayment_per_cycle" class="mb-2 block text-sm font-medium text-gray-700">
                                Repayment Per Cycle (Monthly) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500">
                                    {{ currency_label() }}
                                </div>
                                <input
                                    type="number"
                                    id="repayment_per_cycle"
                                    name="repayment_per_cycle"
                                    x-model="repaymentPerCycle"
                                    @input="calculateEstimatedMonths()"
                                    step="0.01"
                                    min="0.01"
                                    value="{{ old('repayment_per_cycle') }}"
                                    class="block w-full rounded-lg border border-gray-300 py-3 pl-12 pr-12 text-lg font-semibold focus:border-transparent focus:ring-2 focus:ring-orange-500"
                                    placeholder="0.00"
                                    required
                                >
                            </div>
                            <p class="mt-1 text-xs text-gray-500">Amount to be deducted from monthly payroll</p>
                            @error('repayment_per_cycle')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="purpose" class="mb-2 block text-sm font-medium text-gray-700">
                                Purpose <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                id="purpose"
                                name="purpose"
                                value="{{ old('purpose') }}"
                                class="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-orange-500"
                                placeholder="e.g., Emergency, Medical, Education, etc."
                                required
                            >
                            <p class="mt-1 text-xs text-gray-500">Brief description of the loan purpose</p>
                            @error('purpose')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="issue_date" class="mb-2 block text-sm font-medium text-gray-700">
                                Issue Date <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="date"
                                id="issue_date"
                                name="issue_date"
                                value="{{ old('issue_date', date('Y-m-d')) }}"
                                class="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-orange-500"
                                required
                            >
                            @error('issue_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="notes" class="mb-2 block text-sm font-medium text-gray-700">
                                Additional Notes <span class="text-gray-400">(Optional)</span>
                            </label>
                            <textarea
                                id="notes"
                                name="notes"
                                rows="4"
                                class="block w-full rounded-lg border border-gray-300 px-4 py-3 focus:border-transparent focus:ring-2 focus:ring-orange-500"
                                placeholder="Any additional information about this loan..."
                            >{{ old('notes') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">Optional notes or terms for this loan</p>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-yellow-800">Important Information</h4>
                                    <ul class="mt-2 list-inside list-disc space-y-1 text-sm text-yellow-700">
                                        <li>Repayment will be automatically deducted from monthly payroll</li>
                                        <li>Loan will be marked as completed when balance reaches zero</li>
                                        <li>Employee will see loan deductions on their payroll records</li>
                                        <li>You can cancel the loan before it's fully repaid if needed</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between border-t border-gray-200 pt-6">
                            <a href="{{ route('loans.index') }}" class="rounded-lg border border-gray-300 px-6 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                                Cancel
                            </a>
                            <button
                                type="submit"
                                class="rounded-lg bg-orange-600 px-6 py-3 text-sm font-medium text-white transition-colors hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="!amount || !repaymentPerCycle || amount <= 0 || repaymentPerCycle <= 0"
                            >
                                Issue Loan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-lg bg-gradient-to-br from-orange-500 to-red-600 p-6 text-white shadow-lg">
                    <h3 class="mb-4 text-lg font-semibold">Loan Preview</h3>
                    <div class="space-y-3">
                        <div class="border-b border-orange-400 pb-3">
                            <p class="text-sm opacity-90">Total Loan Amount</p>
                            <p class="text-2xl font-bold" x-text="formatCurrency(amount)"></p>
                        </div>
                        <div class="border-b border-orange-400 pb-3">
                            <p class="text-sm opacity-90">Monthly Deduction</p>
                            <p class="text-xl font-bold" x-text="formatCurrency(repaymentPerCycle)"></p>
                        </div>
                        <div class="pt-2">
                            <p class="text-sm opacity-90">Estimated Duration</p>
                            <p class="text-xl font-bold">
                                <span x-text="estimatedMonths"></span>
                                <span x-text="estimatedMonths === 1 ? 'month' : 'months'"></span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg bg-white p-6 shadow">
                    <h3 class="mb-4 text-lg font-semibold text-gray-900">Repayment Schedule</h3>
                    <template x-if="amount > 0 && repaymentPerCycle > 0">
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Loan Amount:</span>
                                <span class="font-semibold text-gray-900" x-text="formatCurrency(amount)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Per Month:</span>
                                <span class="font-semibold text-orange-600" x-text="formatCurrency(-repaymentPerCycle)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Duration:</span>
                                <span class="font-semibold text-gray-900"><span x-text="estimatedMonths"></span> months</span>
                            </div>
                            <div class="border-t border-gray-200 pt-3">
                                <p class="text-xs italic text-gray-500">
                                    <span x-text="formatNumber(repaymentPerCycle)"></span> will be deducted from payroll each month until the loan is fully repaid.
                                </p>
                            </div>
                        </div>
                    </template>
                    <template x-if="!amount || !repaymentPerCycle || amount <= 0 || repaymentPerCycle <= 0">
                        <p class="text-sm italic text-gray-500">Enter loan details to see repayment schedule</p>
                    </template>
                </div>

                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                    <h4 class="mb-3 text-sm font-medium text-blue-900">Example Scenarios</h4>
                    <div class="space-y-3 text-sm text-blue-800">
                        <div class="rounded bg-white p-3">
                            <p class="font-medium text-blue-900">Quick Advance</p>
                            <p class="mt-1 text-xs">{{ currency_label() }} 10,000 @ {{ currency_label() }} 5,000/month = 2 months</p>
                        </div>
                        <div class="rounded bg-white p-3">
                            <p class="font-medium text-blue-900">Medium Term</p>
                            <p class="mt-1 text-xs">{{ currency_label() }} 30,000 @ {{ currency_label() }} 5,000/month = 6 months</p>
                        </div>
                        <div class="rounded bg-white p-3">
                            <p class="font-medium text-blue-900">Long Term</p>
                            <p class="mt-1 text-xs">{{ currency_label() }} 60,000 @ {{ currency_label() }} 5,000/month = 12 months</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-green-800">Quick Tips</h4>
                            <ul class="mt-2 list-inside list-disc space-y-1 text-xs text-green-700">
                                <li>Set realistic monthly deductions</li>
                                <li>Consider employee's salary</li>
                                <li>Check existing active loans</li>
                                <li>Document the purpose clearly</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function loanForm() {
        return {
            selectedEmployeeId: '{{ old('employee_id') }}',
            selectedEmployee: null,
            amount: {{ old('amount', 0) }},
            repaymentPerCycle: {{ old('repayment_per_cycle', 0) }},
            estimatedMonths: 0,
            currencyMeta: window.appCurrency || { code: 'USD', symbol: '$', precision: 2 },

            init() {
                this.updateEmployeeInfo();
                this.calculateEstimatedMonths();
            },

            updateEmployeeInfo() {
                if (!this.selectedEmployeeId) {
                    this.selectedEmployee = null;
                    return;
                }

                const select = document.getElementById('employee_id');
                const option = select.options[select.selectedIndex];

                if (option && option.value) {
                    this.selectedEmployee = {
                        name: option.dataset.name,
                        email: option.dataset.email,
                        salary: parseFloat(option.dataset.salary) || 0,
                        activeLoans: parseFloat(option.dataset.activeLoans) || 0
                    };
                } else {
                    this.selectedEmployee = null;
                }
            },

            calculateEstimatedMonths() {
                this.amount = parseFloat(this.amount) || 0;
                this.repaymentPerCycle = parseFloat(this.repaymentPerCycle) || 0;

                if (this.amount > 0 && this.repaymentPerCycle > 0) {
                    this.estimatedMonths = Math.ceil(this.amount / this.repaymentPerCycle);
                } else {
                    this.estimatedMonths = 0;
                }
            },

            formatNumber(value) {
                return parseFloat(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            },

            get currencyLabel() {
                return this.currencyMeta.code;
            },

            formatCurrency(value) {
                const amount = parseFloat(value ?? 0) || 0;
                const precision = Number.isInteger(this.currencyMeta.precision) ? this.currencyMeta.precision : 2;
                const formatted = Math.abs(amount).toLocaleString('en-US', {
                    minimumFractionDigits: precision,
                    maximumFractionDigits: precision,
                });
                const sign = amount < 0 ? '-' : '';

                return `${sign}${this.currencyLabel} ${formatted}`;
            }
        }
    }
</script>
@endpush
