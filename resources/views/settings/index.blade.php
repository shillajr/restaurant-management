<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Settings - Restaurant Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-8 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
                    <p class="mt-2 text-sm text-gray-600">Manage your restaurant management system preferences</p>
                </div>
                <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    Back to Dashboard
                </a>
            </div>

            <!-- Settings Tabs -->
            <div x-data="{ activeTab: 'general' }" class="bg-white shadow-md rounded-lg overflow-hidden">
                <!-- Tab Navigation -->
                <div class="border-b border-gray-200">
                    <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                        <button @click="activeTab = 'general'" 
                                :class="activeTab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            General
                        </button>
                        <button @click="activeTab = 'restaurant'" 
                                :class="activeTab === 'restaurant' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            Restaurant Info
                        </button>
                        <button @click="activeTab = 'notifications'" 
                                :class="activeTab === 'notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            Notifications
                        </button>
                        <button @click="activeTab = 'integration'" 
                                :class="activeTab === 'integration' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                            </svg>
                            Integrations
                        </button>
                        <button @click="activeTab = 'security'" 
                                :class="activeTab === 'security' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Security
                        </button>
                        <button @click="activeTab = 'items'" 
                                :class="activeTab === 'items' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            Items
                        </button>
                        <button @click="activeTab = 'users'" 
                                :class="activeTab === 'users' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            Users
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6">
                    <form action="{{ route('settings.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- General Settings -->
                        <div x-show="activeTab === 'general'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">General Settings</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Timezone
                                    </label>
                                    <select name="timezone" id="timezone" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="America/New_York">Eastern Time (ET)</option>
                                        <option value="America/Chicago">Central Time (CT)</option>
                                        <option value="America/Denver">Mountain Time (MT)</option>
                                        <option value="America/Los_Angeles" selected>Pacific Time (PT)</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                        Currency
                                    </label>
                                    <select name="currency" id="currency" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="USD" selected>USD ($)</option>
                                        <option value="EUR">EUR (€)</option>
                                        <option value="GBP">GBP (£)</option>
                                        <option value="CAD">CAD ($)</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="date_format" class="block text-sm font-medium text-gray-700 mb-2">
                                        Date Format
                                    </label>
                                    <select name="date_format" id="date_format" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="m/d/Y" selected>MM/DD/YYYY</option>
                                        <option value="d/m/Y">DD/MM/YYYY</option>
                                        <option value="Y-m-d">YYYY-MM-DD</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                                        Language
                                    </label>
                                    <select name="language" id="language" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="en" selected>English</option>
                                        <option value="es">Spanish</option>
                                        <option value="fr">French</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Restaurant Info -->
                        <div x-show="activeTab === 'restaurant'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">Restaurant Information</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="restaurant_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Restaurant Name
                                    </label>
                                    <input type="text" name="restaurant_name" id="restaurant_name" 
                                           value="My Restaurant" 
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                        Address
                                    </label>
                                    <input type="text" name="address" id="address" 
                                           value="123 Main Street" 
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                        City
                                    </label>
                                    <input type="text" name="city" id="city" 
                                           value="San Francisco" 
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                        State/Province
                                    </label>
                                    <input type="text" name="state" id="state" 
                                           value="CA" 
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="zip" class="block text-sm font-medium text-gray-700 mb-2">
                                        ZIP/Postal Code
                                    </label>
                                    <input type="text" name="zip" id="zip" 
                                           value="94102" 
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="tel" name="phone" id="phone" 
                                           value="(555) 123-4567" 
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address
                                    </label>
                                    <input type="email" name="email" id="email" 
                                           value="info@myrestaurant.com" 
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                                        Website
                                    </label>
                                    <input type="url" name="website" id="website" 
                                           value="https://myrestaurant.com" 
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                        </div>

                        <!-- Notifications -->
                        <div x-show="activeTab === 'notifications'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">Notification Preferences</h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="notify_requisitions" name="notify_requisitions" type="checkbox" checked 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="notify_requisitions" class="font-medium text-gray-700">Requisition Notifications</label>
                                        <p class="text-gray-500">Receive notifications when new requisitions are submitted or approved</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="notify_expenses" name="notify_expenses" type="checkbox" checked 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="notify_expenses" class="font-medium text-gray-700">Expense Notifications</label>
                                        <p class="text-gray-500">Get notified when expenses exceed budget thresholds</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="notify_purchase_orders" name="notify_purchase_orders" type="checkbox" checked 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="notify_purchase_orders" class="font-medium text-gray-700">Purchase Order Updates</label>
                                        <p class="text-gray-500">Receive updates on purchase order status changes</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="notify_payroll" name="notify_payroll" type="checkbox" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="notify_payroll" class="font-medium text-gray-700">Payroll Reminders</label>
                                        <p class="text-gray-500">Get reminders for upcoming payroll processing dates</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="notify_email_daily" name="notify_email_daily" type="checkbox" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="notify_email_daily" class="font-medium text-gray-700">Daily Summary Emails</label>
                                        <p class="text-gray-500">Receive a daily summary of all activities</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Integrations -->
                        <div x-show="activeTab === 'integration'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">Integration Settings</h3>
                            
                            <div class="space-y-6">
                                <!-- Loyverse Integration -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center">
                                            <div class="bg-blue-100 rounded-lg p-2">
                                                <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <h4 class="text-sm font-medium text-gray-900">Loyverse POS</h4>
                                                <p class="text-sm text-gray-500">Sync sales data from Loyverse</p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">Connected</span>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <label for="loyverse_api_key" class="block text-sm font-medium text-gray-700 mb-1">
                                                API Key
                                            </label>
                                            <input type="password" name="loyverse_api_key" id="loyverse_api_key" 
                                                   value="••••••••••••••••" 
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="loyverse_auto_sync" checked 
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <span class="ml-2 text-sm text-gray-700">Enable automatic daily sync at 2:00 AM</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Email Integration -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center">
                                            <div class="bg-purple-100 rounded-lg p-2">
                                                <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <h4 class="text-sm font-medium text-gray-900">Email Service</h4>
                                                <p class="text-sm text-gray-500">Configure SMTP settings</p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">Not Configured</span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-1">
                                                SMTP Host
                                            </label>
                                            <input type="text" name="smtp_host" id="smtp_host" 
                                                   placeholder="smtp.gmail.com" 
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-1">
                                                SMTP Port
                                            </label>
                                            <input type="text" name="smtp_port" id="smtp_port" 
                                                   placeholder="587" 
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security -->
                        <div x-show="activeTab === 'security'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">Security Settings</h3>
                            
                            <div class="space-y-6">
                                <!-- Change Password -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <h4 class="text-sm font-medium text-gray-900 mb-4">Change Password</h4>
                                    <div class="space-y-3">
                                        <div>
                                            <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">
                                                Current Password
                                            </label>
                                            <input type="password" name="current_password" id="current_password" 
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        <div>
                                            <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">
                                                New Password
                                            </label>
                                            <input type="password" name="new_password" id="new_password" 
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        <div>
                                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">
                                                Confirm New Password
                                            </label>
                                            <input type="password" name="confirm_password" id="confirm_password" 
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                    </div>
                                </div>

                                <!-- Security Options -->
                                <div class="space-y-4">
                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="two_factor" name="two_factor" type="checkbox" 
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="two_factor" class="font-medium text-gray-700">Enable Two-Factor Authentication</label>
                                            <p class="text-gray-500">Add an extra layer of security to your account</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="session_timeout" name="session_timeout" type="checkbox" checked 
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="session_timeout" class="font-medium text-gray-700">Automatic Session Timeout</label>
                                            <p class="text-gray-500">Log out automatically after 30 minutes of inactivity</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                            <input id="login_alerts" name="login_alerts" type="checkbox" checked 
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="login_alerts" class="font-medium text-gray-700">Login Alerts</label>
                                            <p class="text-gray-500">Receive email notifications for new login attempts</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Activity Log -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-sm font-medium text-gray-900">Recent Activity</h4>
                                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">Last login</span>
                                            <span class="text-gray-900">Today at 2:45 PM</span>
                                        </div>
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">Password changed</span>
                                            <span class="text-gray-900">15 days ago</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Items Management -->
                        <div x-show="activeTab === 'items'" 
                             x-data="{
                                showAddModal: false,
                                showCategoryModal: false,
                                showVendorModal: false,
                                editingItem: null,
                                newCategory: '',
                                newVendor: '',
                                searchQuery: '',
                                filterCategory: '',
                                filterStatus: '',
                                categories: [
                                    'Vegetables', 'Fruits', 'Meat', 'Seafood', 'Dairy', 'Grains',
                                    'Cooking Oils', 'Spices', 'Beverages', 'Cleaning Supplies', 'Office Supplies'
                                ],
                                vendors: [
                                    'Fresh Farm Suppliers', 'Quality Meats Ltd', 'Premium Foods Co',
                                    'Grain Wholesalers', 'Office Essentials', 'Cleaning Pro Supply',
                                    'Ocean Fresh Suppliers', 'Dairy Delights Co', 'Spice Market Ltd',
                                    'Coffee Masters', 'Tea Traders'
                                ],
                                items: [
                                    { id: 1, name: 'Tomatoes', category: 'Vegetables', uom: 'kg', vendor: 'Fresh Farm Suppliers', price: 3500, status: 'active', stock: 45, reorder_level: 20 },
                                    { id: 2, name: 'Chicken Breast', category: 'Meat', uom: 'kg', vendor: 'Quality Meats Ltd', price: 12000, status: 'active', stock: 30, reorder_level: 15 },
                                    { id: 3, name: 'Olive Oil', category: 'Cooking Oils', uom: 'L', vendor: 'Premium Foods Co', price: 8500, status: 'active', stock: 12, reorder_level: 10 },
                                    { id: 4, name: 'Rice (Basmati)', category: 'Grains', uom: 'kg', vendor: 'Grain Wholesalers', price: 4200, status: 'active', stock: 100, reorder_level: 50 },
                                    { id: 5, name: 'Paper Towels', category: 'Cleaning Supplies', uom: 'box', vendor: 'Office Essentials', price: 15000, status: 'active', stock: 8, reorder_level: 5 },
                                    { id: 6, name: 'Dish Soap', category: 'Cleaning Supplies', uom: 'L', vendor: 'Cleaning Pro Supply', price: 6000, status: 'inactive', stock: 0, reorder_level: 5 }
                                ],
                                get filteredItems() {
                                    return this.items.filter(item => {
                                        const matchesSearch = item.name.toLowerCase().includes(this.searchQuery.toLowerCase()) || 
                                                            item.vendor.toLowerCase().includes(this.searchQuery.toLowerCase());
                                        const matchesCategory = !this.filterCategory || item.category === this.filterCategory;
                                        const matchesStatus = !this.filterStatus || item.status === this.filterStatus;
                                        return matchesSearch && matchesCategory && matchesStatus;
                                    });
                                },
                                addItem() {
                                    this.editingItem = null;
                                    this.showAddModal = true;
                                },
                                editItem(item) {
                                    this.editingItem = {...item};
                                    this.showAddModal = true;
                                },
                                deleteItem(id) {
                                    if (confirm('Are you sure you want to delete this item?')) {
                                        this.items = this.items.filter(item => item.id !== id);
                                    }
                                },
                                toggleStatus(item) {
                                    item.status = item.status === 'active' ? 'inactive' : 'active';
                                },
                                addCategory() {
                                    if (this.newCategory.trim() && !this.categories.includes(this.newCategory.trim())) {
                                        this.categories.push(this.newCategory.trim());
                                        this.newCategory = '';
                                        this.showCategoryModal = false;
                                    }
                                },
                                deleteCategory(category) {
                                    const hasItems = this.items.some(item => item.category === category);
                                    if (hasItems) {
                                        alert('Cannot delete category that has items assigned to it.');
                                        return;
                                    }
                                    if (confirm('Are you sure you want to delete this category?')) {
                                        this.categories = this.categories.filter(cat => cat !== category);
                                    }
                                },
                                addVendor() {
                                    if (this.newVendor.trim() && !this.vendors.includes(this.newVendor.trim())) {
                                        this.vendors.push(this.newVendor.trim());
                                        this.vendors.sort();
                                        this.newVendor = '';
                                        this.showVendorModal = false;
                                    }
                                },
                                deleteVendor(vendor) {
                                    const hasItems = this.items.some(item => item.vendor === vendor);
                                    if (hasItems) {
                                        alert('Cannot delete vendor that has items assigned to it.');
                                        return;
                                    }
                                    if (confirm('Are you sure you want to delete this vendor?')) {
                                        this.vendors = this.vendors.filter(v => v !== vendor);
                                    }
                                }
                             }">
                            <div class="space-y-6">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">Items</h3>
                                        <p class="mt-1 text-sm text-gray-600">Manage your item master for purchase requisitions</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button @click="showVendorModal = true" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Add New Vendor
                                        </button>
                                        <button @click="showCategoryModal = true" type="button" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Add New Category
                                        </button>
                                        <button @click="addItem()" type="button" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                            </svg>
                                            Add New Item
                                        </button>
                                    </div>
                                </div>

                                <!-- Search and Filters -->
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Search Items</label>
                                            <input type="text" x-model="searchQuery" placeholder="Search by name or vendor..." class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Category</label>
                                            <select x-model="filterCategory" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                <option value="">All Categories</option>
                                                <template x-for="category in categories" :key="category">
                                                    <option :value="category" x-text="category"></option>
                                                </template>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Filter by Status</label>
                                            <select x-model="filterStatus" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                <option value="">All Status</option>
                                                <option value="active">Active</option>
                                                <option value="inactive">Inactive</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Items Table -->
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Name</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">UoM</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price (TZS)</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            <template x-for="item in filteredItems" :key="item.id">
                                                <tr>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm font-medium text-gray-900" x-text="item.name"></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-600" x-text="item.category"></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-600" x-text="item.uom"></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-600" x-text="item.vendor"></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm text-gray-900" x-text="item.price.toLocaleString()"></div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <div class="text-sm" :class="item.stock <= item.reorder_level ? 'text-red-600 font-semibold' : 'text-gray-600'" x-text="item.stock"></div>
                                                        <div x-show="item.stock <= item.reorder_level" class="text-xs text-red-500">Low stock!</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <button @click="toggleStatus(item)" type="button">
                                                            <span x-show="item.status === 'active'" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>
                                                            <span x-show="item.status === 'inactive'" class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Inactive</span>
                                                        </button>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                        <button @click="editItem(item)" type="button" class="text-blue-600 hover:text-blue-900 mr-3">Edit</button>
                                                        <button @click="deleteItem(item.id)" type="button" class="text-red-600 hover:text-red-900">Delete</button>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr x-show="filteredItems.length === 0">
                                                <td colspan="8" class="px-6 py-4 text-center text-sm text-gray-500">
                                                    No items found matching your criteria.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Add/Edit Item Modal -->
                                <div x-show="showAddModal" 
                                     class="fixed inset-0 z-50 overflow-y-auto" 
                                     x-cloak
                                     style="display: none;">
                                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showAddModal = false"></div>
                                        
                                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <div class="flex items-start justify-between mb-4">
                                                    <h3 class="text-lg font-medium text-gray-900" x-text="editingItem ? 'Edit Item' : 'Add New Item'"></h3>
                                                    <button @click="showAddModal = false" class="text-gray-400 hover:text-gray-500">
                                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>

                                                <form action="{{ route('items.store') }}" method="POST" class="space-y-4">
                                                    @csrf
                                                    <input type="hidden" x-model="editingItem?.id" name="id">

                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                        <!-- Item Name -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Item Name <span class="text-red-500">*</span>
                                                            </label>
                                                            <input type="text" name="name" x-model="editingItem?.name" required 
                                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                        </div>

                                                        <!-- Category -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Category <span class="text-red-500">*</span>
                                                            </label>
                                                            <select name="category" x-model="editingItem?.category" required 
                                                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                                <option value="">Select category</option>
                                                                <template x-for="category in categories" :key="category">
                                                                    <option :value="category" x-text="category"></option>
                                                                </template>
                                                            </select>
                                                        </div>

                                                        <!-- Unit of Measure -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Unit of Measure <span class="text-red-500">*</span>
                                                            </label>
                                                            <select name="uom" x-model="editingItem?.uom" required 
                                                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                                <option value="">Select UoM</option>
                                                                <option value="kg">Kilograms (kg)</option>
                                                                <option value="g">Grams (g)</option>
                                                                <option value="lbs">Pounds (lbs)</option>
                                                                <option value="oz">Ounces (oz)</option>
                                                                <option value="L">Liters (L)</option>
                                                                <option value="ml">Milliliters (ml)</option>
                                                                <option value="pc">Pieces (pc)</option>
                                                                <option value="dozen">Dozen</option>
                                                                <option value="box">Boxes</option>
                                                                <option value="pack">Packs</option>
                                                            </select>
                                                        </div>

                                                        <!-- Vendor -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Vendor/Supplier <span class="text-red-500">*</span>
                                                            </label>
                                                            <select name="vendor" x-model="editingItem?.vendor" required 
                                                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                                <option value="">Select vendor</option>
                                                                <template x-for="vendor in vendors" :key="vendor">
                                                                    <option :value="vendor" x-text="vendor"></option>
                                                                </template>
                                                            </select>
                                                        </div>

                                                        <!-- Price -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Price (TZS) <span class="text-red-500">*</span>
                                                            </label>
                                                            <input type="number" name="price" x-model="editingItem?.price" required min="0" step="0.01"
                                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                        </div>

                                                        <!-- Status -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Status <span class="text-red-500">*</span>
                                                            </label>
                                                            <select name="status" x-model="editingItem?.status" required 
                                                                    class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                                <option value="active">Active</option>
                                                                <option value="inactive">Inactive</option>
                                                            </select>
                                                        </div>

                                                        <!-- Current Stock -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Current Stock
                                                            </label>
                                                            <input type="number" name="stock" x-model="editingItem?.stock" min="0" step="0.01"
                                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                        </div>

                                                        <!-- Reorder Level -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Reorder Level
                                                                <span class="text-gray-500 text-xs">(Low stock alert threshold)</span>
                                                            </label>
                                                            <input type="number" name="reorder_level" x-model="editingItem?.reorder_level" min="0" step="0.01"
                                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                        </div>
                                                    </div>

                                                    <!-- Additional Info -->
                                                    <div class="bg-blue-50 p-4 rounded-lg">
                                                        <h4 class="text-sm font-medium text-blue-900 mb-2">Item Master Requirements</h4>
                                                        <ul class="text-xs text-blue-800 space-y-1">
                                                            <li>✓ All fields marked with <span class="text-red-500">*</span> are required</li>
                                                            <li>✓ Only active items will appear in requisition dropdowns</li>
                                                            <li>✓ Price should reflect current vendor pricing</li>
                                                            <li>✓ Set reorder level to trigger low stock alerts</li>
                                                        </ul>
                                                    </div>

                                                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse">
                                                        <button type="submit" 
                                                                class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm">
                                                            <span x-text="editingItem ? 'Update Item' : 'Add Item'"></span>
                                                        </button>
                                                        <button @click="showAddModal = false" 
                                                                type="button" 
                                                                class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:w-auto sm:text-sm">
                                                            Cancel
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Category Modal -->
                                <div x-show="showCategoryModal" 
                                     class="fixed inset-0 z-50 overflow-y-auto" 
                                     x-cloak
                                     style="display: none;">
                                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showCategoryModal = false"></div>
                                        
                                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <div class="flex items-start justify-between mb-4">
                                                    <h3 class="text-lg font-medium text-gray-900">Manage Categories</h3>
                                                    <button @click="showCategoryModal = false" class="text-gray-400 hover:text-gray-500">
                                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>

                                                <!-- Add New Category Form -->
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                                        Add New Category
                                                    </label>
                                                    <div class="flex gap-2">
                                                        <input type="text" 
                                                               x-model="newCategory" 
                                                               @keydown.enter="addCategory()"
                                                               placeholder="Enter category name"
                                                               class="flex-1 px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                        <button @click="addCategory()" 
                                                                type="button"
                                                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                            </svg>
                                                            Add
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Categories List -->
                                                <div class="border rounded-lg">
                                                    <div class="bg-gray-50 px-4 py-2 border-b">
                                                        <h4 class="text-sm font-medium text-gray-900">Existing Categories</h4>
                                                    </div>
                                                    <div class="max-h-64 overflow-y-auto">
                                                        <template x-for="category in categories" :key="category">
                                                            <div class="flex items-center justify-between px-4 py-3 border-b last:border-b-0 hover:bg-gray-50">
                                                                <span class="text-sm text-gray-900" x-text="category"></span>
                                                                <button @click="deleteCategory(category)" 
                                                                        type="button"
                                                                        class="text-red-600 hover:text-red-900 text-sm">
                                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <div class="mt-4 flex justify-end">
                                                    <button @click="showCategoryModal = false" 
                                                            type="button" 
                                                            class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:text-sm">
                                                        Done
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Add Vendor Modal -->
                                <div x-show="showVendorModal" 
                                     class="fixed inset-0 z-50 overflow-y-auto" 
                                     x-cloak
                                     style="display: none;">
                                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showVendorModal = false"></div>
                                        
                                        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <div class="flex items-start justify-between mb-4">
                                                    <h3 class="text-lg font-medium text-gray-900">Manage Vendors</h3>
                                                    <button @click="showVendorModal = false" class="text-gray-400 hover:text-gray-500">
                                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>

                                                <!-- Add New Vendor Form -->
                                                <div class="mb-4">
                                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                                        Add New Vendor
                                                    </label>
                                                    <div class="flex gap-2">
                                                        <input type="text" 
                                                               x-model="newVendor" 
                                                               @keydown.enter="addVendor()"
                                                               placeholder="Enter vendor name"
                                                               class="flex-1 px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                        <button @click="addVendor()" 
                                                                type="button"
                                                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                                            <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                            </svg>
                                                            Add
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Vendors List -->
                                                <div class="border rounded-lg">
                                                    <div class="bg-gray-50 px-4 py-2 border-b">
                                                        <h4 class="text-sm font-medium text-gray-900">Existing Vendors</h4>
                                                    </div>
                                                    <div class="max-h-64 overflow-y-auto">
                                                        <template x-for="vendor in vendors" :key="vendor">
                                                            <div class="flex items-center justify-between px-4 py-3 border-b last:border-b-0 hover:bg-gray-50">
                                                                <span class="text-sm text-gray-900" x-text="vendor"></span>
                                                                <button @click="deleteVendor(vendor)" 
                                                                        type="button"
                                                                        class="text-red-600 hover:text-red-900 text-sm">
                                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>

                                                <div class="mt-4 flex justify-end">
                                                    <button @click="showVendorModal = false" 
                                                            type="button" 
                                                            class="inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:text-sm">
                                                        Done
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- User Management -->
                        <div x-show="activeTab === 'users'" 
                             x-data="{
                                showAddModal: false,
                                editingUser: null,
                                users: [
                                    { id: 1, name: 'Admin User', email: 'admin@example.com', role: 'admin', status: 'active', last_login: '2 hours ago' },
                                    { id: 2, name: 'Manager User', email: 'manager@example.com', role: 'manager', status: 'active', last_login: '5 hours ago' },
                                    { id: 3, name: 'Chef User', email: 'chef@example.com', role: 'chef', status: 'active', last_login: '1 day ago' },
                                    { id: 4, name: 'Purchaser User', email: 'purchaser@example.com', role: 'purchaser', status: 'active', last_login: '3 days ago' }
                                ],
                                addUser() {
                                    this.editingUser = null;
                                    this.showAddModal = true;
                                },
                                editUser(user) {
                                    this.editingUser = {...user};
                                    this.showAddModal = true;
                                }
                             }"
                             class="space-y-6">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">User Management</h3>
                                <button @click="addUser()" 
                                        type="button"
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                    </svg>
                                    Add New User
                                </button>
                            </div>

                            <!-- Users Table -->
                            <div class="border border-gray-200 rounded-lg overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <template x-for="user in users" :key="user.id">
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                                            <span class="text-blue-600 font-medium text-sm" x-text="user.name.charAt(0)"></span>
                                                        </div>
                                                        <div class="ml-4">
                                                            <div class="text-sm font-medium text-gray-900" x-text="user.name"></div>
                                                            <div class="text-sm text-gray-500" x-text="user.email"></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span x-text="user.role" 
                                                          :class="{
                                                              'bg-purple-100 text-purple-800': user.role === 'admin',
                                                              'bg-blue-100 text-blue-800': user.role === 'manager',
                                                              'bg-green-100 text-green-800': user.role === 'chef',
                                                              'bg-yellow-100 text-yellow-800': user.role === 'purchaser'
                                                          }"
                                                          class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize">
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span :class="user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" 
                                                          class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full capitalize"
                                                          x-text="user.status">
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="user.last_login"></td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button @click="editUser(user)" 
                                                            type="button"
                                                            class="text-blue-600 hover:text-blue-900 mr-4">
                                                        Edit
                                                    </button>
                                                    <button type="button" 
                                                            class="text-red-600 hover:text-red-900">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Add/Edit User Modal -->
                            <div x-show="showAddModal" 
                                 x-cloak
                                 class="fixed inset-0 z-50 overflow-y-auto" 
                                 aria-labelledby="modal-title" 
                                 role="dialog" 
                                 aria-modal="true">
                                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                    <div x-show="showAddModal" 
                                         x-transition:enter="ease-out duration-300"
                                         x-transition:enter-start="opacity-0"
                                         x-transition:enter-end="opacity-100"
                                         x-transition:leave="ease-in duration-200"
                                         x-transition:leave-start="opacity-100"
                                         x-transition:leave-end="opacity-0"
                                         class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                                         @click="showAddModal = false"></div>

                                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

                                    <div x-show="showAddModal"
                                         x-transition:enter="ease-out duration-300"
                                         x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                         x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                         x-transition:leave="ease-in duration-200"
                                         x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                         x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                         class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                            <div class="sm:flex sm:items-start">
                                                <div class="w-full mt-3 text-center sm:mt-0 sm:text-left">
                                                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                                        <span x-text="editingUser ? 'Edit User' : 'Add New User'"></span>
                                                    </h3>
                                                    <div class="space-y-4">
                                                        <!-- Name -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                Full Name <span class="text-red-500">*</span>
                                                            </label>
                                                            <input type="text" 
                                                                   :value="editingUser ? editingUser.name : ''"
                                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                                   placeholder="John Doe">
                                                        </div>

                                                        <!-- Email -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                Email Address <span class="text-red-500">*</span>
                                                            </label>
                                                            <input type="email" 
                                                                   :value="editingUser ? editingUser.email : ''"
                                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                                   placeholder="john@example.com">
                                                        </div>

                                                        <!-- Role -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                Role <span class="text-red-500">*</span>
                                                            </label>
                                                            <select class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                                <option value="">Select role</option>
                                                                <option value="admin">Admin</option>
                                                                <option value="manager">Manager</option>
                                                                <option value="chef">Chef</option>
                                                                <option value="purchaser">Purchaser</option>
                                                                <option value="accountant">Accountant</option>
                                                                <option value="viewer">Viewer</option>
                                                            </select>
                                                        </div>

                                                        <!-- Password (only for new users) -->
                                                        <div x-show="!editingUser">
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                Password <span class="text-red-500">*</span>
                                                            </label>
                                                            <input type="password" 
                                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                                                   placeholder="••••••••">
                                                        </div>

                                                        <!-- Permissions -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                                                Permissions
                                                            </label>
                                                            <div class="grid grid-cols-2 gap-3 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">View Requisitions</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">Create Requisitions</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">Approve Requisitions</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">Reject Requisitions</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">View Purchase Orders</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">Create Purchase Orders</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">View Expenses</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">Create Expenses</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">View Reports</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">Manage Payroll</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">View Users</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">Manage Users</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">View Settings</span>
                                                                </label>
                                                                <label class="flex items-center">
                                                                    <input type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                    <span class="ml-2 text-sm text-gray-700">Manage Settings</span>
                                                                </label>
                                                            </div>
                                                        </div>

                                                        <!-- Status -->
                                                        <div>
                                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                                Status
                                                            </label>
                                                            <select class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                                                <option value="active">Active</option>
                                                                <option value="inactive">Inactive</option>
                                                            </select>
                                                        </div>

                                                        <!-- Send Welcome Email -->
                                                        <div x-show="!editingUser">
                                                            <label class="flex items-center">
                                                                <input type="checkbox" checked class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                                <span class="ml-2 text-sm text-gray-700">Send welcome email with login credentials</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                                            <button type="button" 
                                                    @click="showAddModal = false"
                                                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                                <span x-text="editingUser ? 'Update User' : 'Create User'"></span>
                                            </button>
                                            <button type="button" 
                                                    @click="showAddModal = false"
                                                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Role Descriptions -->
                            <div class="bg-gray-50 rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-900 mb-3">Role Descriptions</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                    <div>
                                        <span class="font-medium text-purple-700">Admin:</span>
                                        <span class="text-gray-600"> Full system access and user management</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-blue-700">Manager:</span>
                                        <span class="text-gray-600"> Approve requisitions, view reports</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-green-700">Chef:</span>
                                        <span class="text-gray-600"> Create requisitions, view inventory</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-yellow-700">Purchaser:</span>
                                        <span class="text-gray-600"> Create purchase orders, manage suppliers</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-indigo-700">Accountant:</span>
                                        <span class="text-gray-600"> Manage expenses, view financial reports</span>
                                    </div>
                                    <div>
                                        <span class="font-medium text-gray-700">Viewer:</span>
                                        <span class="text-gray-600"> Read-only access to all modules</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-end space-x-4">
                                <a href="{{ route('dashboard') }}" class="px-6 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    Cancel
                                </a>
                                <button type="submit" class="px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                    <span class="flex items-center">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Save Changes
                                    </span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Info Panel -->
            <div class="mt-6 bg-blue-50 border-l-4 border-blue-400 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Note:</strong> Changes to settings will take effect immediately. Some integrations may require reconnection after updating credentials.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak] { display: none !important; }
    </style>
</body>
</html>
