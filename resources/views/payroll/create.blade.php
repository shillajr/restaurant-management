@extends('layouts.app')

@section('title', 'Create Payroll')

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10" x-data="payrollForm()">
    <div class="mx-auto max-w-7xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Create Payroll</h1>
                <p class="mt-1 text-sm text-gray-600">Generate monthly payroll for employees</p>
            </div>
            <a href="{{ route('payroll.index') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                Cancel
            </a>
        </div>
            @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                <ul class="list-disc list-inside">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                {{ session('error') }}
            </div>
            @endif

            <form method="POST" action="{{ route('payroll.store') }}">
                @csrf

                <!-- Payroll Configuration -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Payroll Configuration</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Month Selection -->
                            <div>
                                <label for="month" class="block text-sm font-medium text-gray-700 mb-2">
                                    Payroll Month <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="month" 
                                    id="month" 
                                    name="month" 
                                    value="{{ old('month', $currentMonth) }}"
                                    x-model="selectedMonth"
                                    @change="fetchEmployeeData()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                    required
                                >
                                <p class="mt-1 text-xs text-gray-500">Select the month for payroll processing</p>
                            </div>

                            <!-- Employee Selection -->
                            <div>
                                <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Employee Selection <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    id="employee_id" 
                                    name="employee_id"
                                    x-model="selectedEmployee"
                                    @change="filterEmployees()"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                >
                                    <option value="">All Employees</option>
                                    @foreach($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Leave blank to process all employees</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Employee Payroll Details -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">Employee Payroll Details</h2>
                            <p class="mt-1 text-sm text-gray-600">Enter absent days and loan deductions for each employee</p>
                        </div>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium" x-text="displayedEmployees.length"></span> employee(s)
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monthly Salary</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Daily Rate</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absent Days</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Absent Deduction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Active Loans</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Deduction</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Payable</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="employee in displayedEmployees" :key="employee.id">
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="employee.name"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="formatCurrency(employee.monthly_salary)"></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600" x-text="formatCurrency(employee.daily_rate)"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input 
                                                type="number" 
                                                :name="'absent_days[' + employee.id + ']'"
                                                x-model.number="employee.absent_days"
                                                @input="calculateDeductions(employee)"
                                                min="0" 
                                                max="31" 
                                                class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                placeholder="0"
                                            >
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600" x-text="formatCurrency(-employee.absent_deduction)"></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <template x-if="employee.active_loans > 0">
                                                <span class="text-sm text-orange-600" x-text="formatCurrency(employee.active_loans)"></span>
                                            </template>
                                            <template x-if="employee.active_loans == 0">
                                                <span class="text-sm text-gray-400">None</span>
                                            </template>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <input 
                                                type="number" 
                                                :name="'loan_deductions[' + employee.id + ']'"
                                                x-model.number="employee.loan_deduction"
                                                @input="calculateDeductions(employee)"
                                                min="0" 
                                                step="0.01"
                                                :max="employee.active_loans"
                                                class="w-32 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                                placeholder="0.00"
                                            >
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600" x-text="formatCurrency(employee.net_payable)"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot class="bg-gray-50">
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-right text-sm font-semibold text-gray-900">Total Net Payable:</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600" x-text="formatCurrency(totalNetPayable)"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Summary Section -->
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Payroll Summary</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="bg-blue-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-blue-600">Total Base Salary</p>
                                <p class="mt-2 text-2xl font-bold text-blue-900" x-text="formatCurrency(totalBaseSalary)"></p>
                            </div>

                            <div class="bg-red-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-red-600">Total Deductions</p>
                                <p class="mt-2 text-2xl font-bold text-red-900" x-text="formatCurrency(-totalAbsentDeductions)"></p>
                                <p class="mt-1 text-xs text-red-600">Absent days only</p>
                            </div>

                            <div class="bg-orange-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-orange-600">Total Loan Deductions</p>
                                <p class="mt-2 text-2xl font-bold text-orange-900" x-text="formatCurrency(-totalLoanDeductions)"></p>
                            </div>

                            <div class="bg-green-50 rounded-lg p-4">
                                <p class="text-sm font-medium text-green-600">Total Net Payable</p>
                                <p class="mt-2 text-2xl font-bold text-green-900" x-text="formatCurrency(totalNetPayable)"></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('payroll.index') }}" class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                        Cancel
                    </a>
                    <button 
                        type="submit" 
                        class="px-6 py-3 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="displayedEmployees.length === 0"
                    >
                        Create Payroll
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function payrollForm() {
        return {
            selectedMonth: '{{ old('month', $currentMonth) }}',
            selectedEmployee: '{{ old('employee_id', '') }}',
            allEmployees: @json($employeesData),
            displayedEmployees: [],
            currencyMeta: window.appCurrency || { code: 'USD', symbol: '$', precision: 2 },

            init() {
                this.filterEmployees();
            },

            filterEmployees() {
                if (this.selectedEmployee) {
                    this.displayedEmployees = this.allEmployees.filter(emp => emp.id == this.selectedEmployee);
                } else {
                    this.displayedEmployees = [...this.allEmployees];
                }
                this.displayedEmployees.forEach(emp => this.calculateDeductions(emp));
            },

            calculateDeductions(employee) {
                const absentDays = parseFloat(employee.absent_days) || 0;
                employee.absent_deduction = absentDays * employee.daily_rate;

                const loanDeduction = parseFloat(employee.loan_deduction) || 0;
                employee.net_payable = employee.monthly_salary - employee.absent_deduction - loanDeduction;
            },

            get totalBaseSalary() {
                return this.displayedEmployees.reduce((sum, emp) => sum + parseFloat(emp.monthly_salary || 0), 0);
            },

            get totalAbsentDeductions() {
                return this.displayedEmployees.reduce((sum, emp) => sum + parseFloat(emp.absent_deduction || 0), 0);
            },

            get totalLoanDeductions() {
                return this.displayedEmployees.reduce((sum, emp) => sum + parseFloat(emp.loan_deduction || 0), 0);
            },

            get totalNetPayable() {
                return this.displayedEmployees.reduce((sum, emp) => sum + parseFloat(emp.net_payable || 0), 0);
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
            },

            fetchEmployeeData() {
                console.log('Month changed to:', this.selectedMonth);
            }
        }
    }
</script>
@endpush
