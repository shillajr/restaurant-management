<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edit Salary - Restaurant Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen" x-data="salaryForm()">
        <!-- Header -->
        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900">Edit Employee Salary</h1>
                        <p class="mt-1 text-sm text-gray-600">Update monthly salary for {{ $employee->name }}</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('employees.salary.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Error Messages -->
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Salary Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Salary Information</h2>
                        </div>
                        <form method="POST" action="{{ route('employees.salary.update', $employee->id) }}" class="p-6 space-y-6">
                            @csrf
                            @method('PUT')

                            <!-- Employee Info Display -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0 h-16 w-16 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <span class="text-indigo-600 font-bold text-xl">
                                            {{ strtoupper(substr($employee->name, 0, 2)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $employee->name }}</h3>
                                        <p class="text-sm text-gray-600">{{ $employee->email }}</p>
                                        @if($employee->roles->isNotEmpty())
                                            <span class="mt-1 inline-block px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                                {{ ucfirst($employee->roles->first()->name) }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Current Salary Display -->
                            @if($employee->monthly_salary > 0)
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-blue-900 mb-2">Current Salary Information</h4>
                                <div class="grid grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <p class="text-blue-700">Monthly Salary</p>
                                        <p class="text-lg font-bold text-blue-900">{{ currency_format($employee->monthly_salary) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-blue-700">Daily Rate</p>
                                        <p class="text-lg font-bold text-blue-900">{{ currency_format($employee->daily_rate) }}</p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Monthly Salary Input -->
                            <div>
                                <label for="monthly_salary" class="block text-sm font-medium text-gray-700 mb-2">
                                    New Monthly Salary <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">{{ currency_label() }}</span>
                                    </div>
                                    <input 
                                        type="number" 
                                        id="monthly_salary" 
                                        name="monthly_salary" 
                                        x-model="monthlySalary"
                                        @input="calculateDailyRate()"
                                        step="0.01" 
                                        min="0" 
                                        value="{{ old('monthly_salary', $employee->monthly_salary) }}"
                                        class="block w-full pl-12 pr-12 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent text-lg font-semibold"
                                        placeholder="0.00"
                                        required
                                    >
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Enter the monthly salary amount in {{ currency_label() }}</p>
                                @error('monthly_salary')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Daily Rate Preview (Auto-calculated) -->
                            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3 flex-1">
                                        <h4 class="text-sm font-medium text-green-800">Daily Rate (Auto-calculated)</h4>
                                        <p class="mt-1 text-2xl font-bold text-green-900">
                                            <span x-text="formatCurrency(dailyRate)"></span>
                                        </p>
                                        <p class="mt-1 text-xs text-green-700">
                                            Calculated as: Monthly Salary ÷ 30 days
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Calculation Examples -->
                            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-yellow-900 mb-3">Examples</h4>
                                <div class="space-y-2 text-sm text-yellow-800">
                                    <template x-if="monthlySalary > 0">
                                        <div>
                                            <p class="font-medium">Based on <span x-text="formatCurrency(monthlySalary)"></span> monthly:</p>
                                            <ul class="mt-1 ml-4 space-y-1 list-disc">
                                                <li>1 day absent = <span x-text="formatCurrency(-dailyRate)"></span></li>
                                                <li>5 days absent = <span x-text="formatCurrency(-(dailyRate * 5))"></span></li>
                                                <li>10 days absent = <span x-text="formatCurrency(-(dailyRate * 10))"></span></li>
                                            </ul>
                                        </div>
                                    </template>
                                    <template x-if="monthlySalary <= 0">
                                        <p class="italic">Enter a salary amount to see calculation examples</p>
                                    </template>
                                </div>
                            </div>

                            <!-- Warning Notice -->
                            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-orange-800">Important Notice</h4>
                                        <ul class="mt-2 text-sm text-orange-700 space-y-1 list-disc list-inside">
                                            <li>This change will affect future payroll calculations</li>
                                            <li>Daily rate is automatically calculated and cannot be manually edited</li>
                                            <li>Existing payroll records will not be affected</li>
                                            <li>Changes take effect immediately after saving</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                                <a href="{{ route('employees.salary.index') }}" class="px-6 py-3 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                    Cancel
                                </a>
                                <button 
                                    type="submit" 
                                    class="px-6 py-3 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                    :disabled="!monthlySalary || monthlySalary < 0"
                                >
                                    Update Salary
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="space-y-6">
                    <!-- Summary Card -->
                    <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg shadow-lg p-6 text-white">
                        <h3 class="text-lg font-semibold mb-4">Salary Preview</h3>
                        <div class="space-y-3">
                            <div class="pb-3 border-b border-indigo-400">
                                <p class="text-sm opacity-90">Monthly Salary</p>
                                <p class="text-2xl font-bold" x-text="formatCurrency(monthlySalary)"></p>
                            </div>
                            <div class="pb-3 border-b border-indigo-400">
                                <p class="text-sm opacity-90">Daily Rate</p>
                                <p class="text-xl font-bold" x-text="formatCurrency(dailyRate)"></p>
                            </div>
                            <div class="pt-2">
                                <p class="text-sm opacity-90">Annual Salary</p>
                                <p class="text-xl font-bold" x-text="formatCurrency(monthlySalary * 12)"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Employee Stats -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Employee Stats</h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-gray-500">Total Payrolls</p>
                                <p class="font-semibold text-gray-900">{{ $employee->payrolls->count() }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500">Outstanding Balance</p>
                                <p class="font-semibold {{ $employee->total_outstanding_balance > 0 ? 'text-red-600' : 'text-green-600' }}">
                                    {{ currency_format($employee->total_outstanding_balance ?? 0) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Active Loans</p>
                                <p class="font-semibold text-orange-600">
                                    {{ currency_format($employee->total_active_loan_balance ?? 0) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-500">Member Since</p>
                                <p class="font-semibold text-gray-900">{{ $employee->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Links</h3>
                        <div class="space-y-2">
                            <a href="{{ route('employees.salary.index') }}" class="block px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                All Employees
                            </a>
                            <a href="{{ route('payroll.index') }}?employee_id={{ $employee->id }}" class="block px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                View Payroll History
                            </a>
                            <a href="{{ route('loans.index') }}?employee_id={{ $employee->id }}" class="block px-4 py-2 text-sm text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                View Loans
                            </a>
                        </div>
                    </div>

                    <!-- Help Text -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h4 class="text-sm font-medium text-blue-800">Need Help?</h4>
                                <ul class="mt-2 text-xs text-blue-700 space-y-1 list-disc list-inside">
                                    <li>Daily rate = Monthly ÷ 30</li>
                                    <li>Changes affect future payrolls only</li>
                                    <li>All calculations are automatic</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        function salaryForm() {
            return {
                monthlySalary: {{ old('monthly_salary', $employee->monthly_salary) ?: 0 }},
                dailyRate: 0,
                currencyMeta: window.appCurrency || { code: 'USD', symbol: '$', precision: 2 },

                init() {
                    this.calculateDailyRate();
                },

                calculateDailyRate() {
                    this.monthlySalary = parseFloat(this.monthlySalary) || 0;
                    this.dailyRate = this.monthlySalary / 30;
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
</body>
</html>
