<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Manage Payroll - Restaurant Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Manage Payroll</h1>
                    <p class="mt-2 text-sm text-gray-600">Process employee payments and generate payroll records</p>
                </div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            <!-- Form -->
            <div class="bg-white shadow-md rounded-lg overflow-hidden">
                <form action="{{ route('payroll.store') }}" method="POST" 
                      x-data="payrollForm()"
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
                                    <h3 class="text-sm font-medium text-red-800">There were errors with your submission</h3>
                                    <ul class="mt-2 text-sm text-red-700 list-disc list-inside">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Payroll Period -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Weekly Payroll Period</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Week Selection -->
                            <div>
                                <label for="week_ending" class="block text-sm font-medium text-gray-700 mb-2">
                                    Week Ending (Sunday) <span class="text-red-500">*</span>
                                </label>
                                <input type="date" 
                                       name="week_ending" 
                                       id="week_ending" 
                                       x-model="weekEnding"
                                       @change="calculateWeekDates()"
                                       class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                <p class="mt-1 text-xs text-gray-500">Select the Sunday when payroll is processed</p>
                                @error('week_ending')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Week Range Display -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Week Range
                                </label>
                                <div class="px-4 py-2 bg-white border border-gray-300 rounded-md">
                                    <p class="text-sm text-gray-900" x-text="weekRange"></p>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Monday to Sunday work week</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Date -->
                    <div>
                        <label for="payment_date" class="block text-sm font-medium text-gray-700 mb-2">
                            Payment Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" 
                               name="payment_date" 
                               id="payment_date" 
                               class="block w-full md:w-1/3 px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                               value="{{ date('Y-m-d') }}"
                               required>
                        @error('payment_date')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Employee Selection -->
                    <div x-data="{ 
                        employees: [
                            {id: 1, name: 'John Smith', role: 'Chef', monthlySalary: 300000, dailyRate: 10000, daysAttended: 5, monthlyBalance: 250000},
                            {id: 2, name: 'Jane Doe', role: 'Sous Chef', monthlySalary: 250000, dailyRate: 8333, daysAttended: 6, monthlyBalance: 200000},
                            {id: 3, name: 'Mike Johnson', role: 'Line Cook', monthlySalary: 200000, dailyRate: 6667, daysAttended: 5, monthlyBalance: 166665},
                            {id: 4, name: 'Sarah Williams', role: 'Server', monthlySalary: 180000, dailyRate: 6000, daysAttended: 6, monthlyBalance: 144000},
                            {id: 5, name: 'Tom Brown', role: 'Dishwasher', monthlySalary: 150000, dailyRate: 5000, daysAttended: 5, monthlyBalance: 125000}
                        ],
                        calculateWeeklyPay(employee) {
                            return (parseFloat(employee.daysAttended || 0) * employee.dailyRate).toFixed(2);
                        },
                        calculateNewBalance(employee) {
                            const weeklyPay = parseFloat(this.calculateWeeklyPay(employee));
                            return (employee.monthlyBalance - weeklyPay).toFixed(2);
                        },
                        getTotalWeeklyPayroll() {
                            return this.employees.reduce((sum, emp) => {
                                const checkbox = document.querySelector(`input[name='employees[${emp.id}][selected]']`);
                                if (checkbox && checkbox.checked) {
                                    return sum + parseFloat(this.calculateWeeklyPay(emp));
                                }
                                return sum;
                            }, 0).toFixed(2);
                        }
                    }">
                        <div class="flex items-center justify-between mb-4">
                            <label class="block text-sm font-medium text-gray-700">
                                Select Employees <span class="text-red-500">*</span>
                            </label>
                            <button type="button" 
                                    @click="selectAll()"
                                    class="text-sm text-blue-600 hover:text-blue-800">
                                Select All
                            </button>
                        </div>

                        <div class="border border-gray-300 rounded-md divide-y divide-gray-200 max-h-96 overflow-y-auto">
                            <template x-for="employee in employees" :key="employee.id">
                                <div class="p-4 hover:bg-gray-50">
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               :name="'employees[' + employee.id + '][selected]'" 
                                               :id="'employee_' + employee.id"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label :for="'employee_' + employee.id" class="ml-3 flex-1 cursor-pointer">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900" x-text="employee.name"></p>
                                                    <p class="text-sm text-gray-500" x-text="employee.role"></p>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-xs text-gray-500">Monthly Salary</p>
                                                    <p class="text-sm font-medium text-gray-900">TZS <span x-text="employee.monthlySalary.toLocaleString()"></span></p>
                                                    <p class="text-xs text-gray-500 mt-1">Daily Rate: TZS <span x-text="employee.dailyRate.toLocaleString()"></span></p>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- Attendance and Payment Calculation -->
                                    <div class="mt-3 ml-7 bg-blue-50 p-3 rounded-md">
                                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                                            <!-- Days Attended -->
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                                    Days Attended <span class="text-red-500">*</span>
                                                </label>
                                                <input type="number" 
                                                       :name="'employees[' + employee.id + '][days_attended]'" 
                                                       x-model="employee.daysAttended"
                                                       min="0"
                                                       max="7"
                                                       class="block w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                       placeholder="0">
                                                <p class="text-xs text-gray-500 mt-1">Max: 7 days</p>
                                            </div>

                                            <!-- Weekly Pay Calculation -->
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Weekly Pay</label>
                                                <div class="px-2 py-1 text-sm bg-white border border-gray-300 rounded-md">
                                                    <p class="font-semibold text-green-700">TZS <span x-text="parseFloat(calculateWeeklyPay(employee)).toLocaleString()"></span></p>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">Auto-calculated</p>
                                            </div>

                                            <!-- Current Monthly Balance -->
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">Current Balance</label>
                                                <div class="px-2 py-1 text-sm bg-white border border-gray-300 rounded-md">
                                                    <p class="font-medium text-blue-700">TZS <span x-text="employee.monthlyBalance.toLocaleString()"></span></p>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">This month</p>
                                            </div>

                                            <!-- New Balance After Payment -->
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">New Balance</label>
                                                <div class="px-2 py-1 text-sm bg-white border border-gray-300 rounded-md">
                                                    <p class="font-medium" 
                                                       :class="parseFloat(calculateNewBalance(employee)) >= 0 ? 'text-gray-700' : 'text-red-700'">
                                                        TZS <span x-text="parseFloat(calculateNewBalance(employee)).toLocaleString()"></span>
                                                    </p>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">After payment</p>
                                            </div>

                                            <!-- Manual Balance Adjustment -->
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700 mb-1">
                                                    Balance Adjustment
                                                </label>
                                                <input type="number" 
                                                       :name="'employees[' + employee.id + '][balance_adjustment]'" 
                                                       step="0.01"
                                                       class="block w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                       placeholder="0.00">
                                                <p class="text-xs text-gray-500 mt-1">Optional</p>
                                            </div>
                                        </div>

                                        <!-- Adjustment Reason -->
                                        <div class="mt-3">
                                            <label class="block text-xs font-medium text-gray-700 mb-1">
                                                Adjustment Reason (if any)
                                            </label>
                                            <input type="text" 
                                                   :name="'employees[' + employee.id + '][adjustment_reason]'" 
                                                   class="block w-full px-2 py-1 text-sm border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                                                   placeholder="e.g., Late attendance upload, back-pay, overtime">
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Payroll Summary -->
                        <div class="mt-4 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-200">
                            <h4 class="text-sm font-semibold text-gray-900 mb-3">Weekly Payroll Summary</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <p class="text-xs text-gray-600">Total Weekly Payroll</p>
                                    <p class="text-lg font-bold text-blue-700">TZS <span x-text="parseFloat(getTotalWeeklyPayroll()).toLocaleString()"></span></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">Employees Selected</p>
                                    <p class="text-lg font-bold text-gray-700" x-text="employees.length"></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-600">Payment Date</p>
                                    <p class="text-lg font-bold text-gray-700" x-text="new Date(weekEnding || new Date()).toLocaleDateString()"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Deductions -->
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Standard Deductions (Optional)</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-2">
                                    Tax Rate (%)
                                </label>
                                <input type="number" 
                                       name="tax_rate" 
                                       id="tax_rate" 
                                       step="0.01"
                                       min="0"
                                       max="100"
                                       value="0"
                                       class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                <p class="mt-1 text-xs text-gray-500">Applied to gross weekly pay</p>
                            </div>
                            <div>
                                <label for="insurance" class="block text-sm font-medium text-gray-700 mb-2">
                                    Insurance Deduction (TZS)
                                </label>
                                <input type="number" 
                                       name="insurance" 
                                       id="insurance" 
                                       step="0.01"
                                       min="0"
                                       value="0"
                                       class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                                <p class="mt-1 text-xs text-gray-500">Fixed amount per employee</p>
                            </div>
                            <div>
                                <label for="other_deductions" class="block text-sm font-medium text-gray-700 mb-2">
                                    Other Deductions (TZS)
                                </label>
                                <input type="number" 
                                       name="other_deductions" 
                                       id="other_deductions" 
                                       step="0.01"
                                       min="0"
                                       value="0"
                                       class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="0.00">
                                <p class="mt-1 text-xs text-gray-500">Loans, advances, etc.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                            Notes
                        </label>
                        <textarea name="notes" 
                                  id="notes" 
                                  rows="3" 
                                  class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Enter any additional notes about this payroll period..."></textarea>
                    </div>

                    <!-- Payroll Calculation Explanation -->
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-blue-800">Payroll Calculation Logic</h4>
                                <div class="mt-2 text-sm text-blue-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        <li><strong>Daily Rate:</strong> Monthly Salary ÷ 30 days</li>
                                        <li><strong>Weekly Pay:</strong> Days Attended × Daily Rate</li>
                                        <li><strong>New Balance:</strong> Current Monthly Balance - Weekly Pay</li>
                                        <li>Absent days (including off-days) are NOT paid</li>
                                        <li>Each payment reduces the monthly salary balance</li>
                                        <li>Unpaid amounts remain in monthly balance for later processing</li>
                                        <li>Manual adjustments can be made for corrections or back-pay</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="send_notifications" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                <span class="ml-2 text-sm text-gray-700">Send email notifications to employees</span>
                            </label>
                        </div>
                        <div class="flex items-center space-x-4">
                            <a href="{{ route('dashboard') }}" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </a>
                            <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <span class="flex items-center">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Process Payroll
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Summary Panel -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Total Employees</p>
                            <p class="text-2xl font-semibold text-gray-900">5</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">Estimated Total</p>
                            <p class="text-2xl font-semibold text-gray-900">-</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-lg shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-500">After Deductions</p>
                            <p class="text-2xl font-semibold text-gray-900">-</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h4 class="text-sm font-medium text-yellow-800 mb-2">Important Payroll Guidelines</h4>
                        <ul class="text-sm text-yellow-700 space-y-1 list-disc list-inside">
                            <li>Payroll must be processed every <strong>Sunday</strong> for the previous week (Monday-Sunday)</li>
                            <li>Verify attendance records before processing to ensure accuracy</li>
                            <li>Monthly balance is reset on the 1st of each month to the employee's full monthly salary</li>
                            <li>If no payment is recorded, unpaid amounts remain in the monthly balance</li>
                            <li>Use balance adjustments for late attendance uploads, corrections, or back-pay</li>
                            <li>Negative balances indicate overpayment and should be reviewed</li>
                            <li>All payroll records are automatically logged for audit purposes</li>
                            <li>Ensure compliance with local labor laws and tax regulations</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Example Calculation -->
            <div class="mt-6 bg-green-50 border border-green-200 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-green-900 mb-3">Example Calculation</h4>
                <div class="text-sm text-green-800 space-y-2">
                    <p><strong>Scenario:</strong> Employee with TZS 300,000 monthly salary works 5 days in a week</p>
                    <div class="ml-4 space-y-1">
                        <p>• Daily Rate = 300,000 ÷ 30 = <strong>TZS 10,000</strong></p>
                        <p>• Weekly Pay = 5 days × 10,000 = <strong>TZS 50,000</strong></p>
                        <p>• New Balance = 300,000 - 50,000 = <strong>TZS 250,000</strong></p>
                    </div>
                    <p class="mt-2"><em>The remaining TZS 250,000 is available for future weekly payments this month.</em></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function payrollForm() {
            return {
                weekEnding: '',
                weekRange: 'Select a week ending date',
                
                init() {
                    // Set default to next Sunday
                    const today = new Date();
                    const nextSunday = new Date(today);
                    const daysUntilSunday = (7 - today.getDay()) % 7;
                    nextSunday.setDate(today.getDate() + daysUntilSunday);
                    this.weekEnding = nextSunday.toISOString().split('T')[0];
                    this.calculateWeekDates();
                },
                
                calculateWeekDates() {
                    if (!this.weekEnding) {
                        this.weekRange = 'Select a week ending date';
                        return;
                    }
                    
                    const sunday = new Date(this.weekEnding);
                    const monday = new Date(sunday);
                    monday.setDate(sunday.getDate() - 6);
                    
                    const formatDate = (date) => {
                        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
                    };
                    
                    this.weekRange = `${formatDate(monday)} - ${formatDate(sunday)}`;
                }
            }
        }

        function selectAll() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][id^="employee_"]');
            const allChecked = Array.from(checkboxes).every(cb => cb.checked);
            checkboxes.forEach(cb => cb.checked = !allChecked);
        }
    </script>
</body>
</html>
