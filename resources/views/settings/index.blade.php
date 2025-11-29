@extends('layouts.app')

@section('title', __('settings.title'))

@php
    use Illuminate\Support\Str;
@endphp

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-6xl">
        <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ __('settings.title') }}</h1>
                <p class="mt-2 text-sm text-gray-600">{{ __('settings.description') }}</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                {{ __('settings.back_to_dashboard') }}
            </a>
        </div>

        @if (session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div x-data="{ activeTab: @js($activeTab) }" class="overflow-hidden rounded-lg bg-white shadow-md">
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
                            {{ __('settings.tabs.general') }}
                        </button>
                        <button @click="activeTab = 'restaurant'" 
                                :class="activeTab === 'restaurant' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                            </svg>
                            {{ __('settings.tabs.restaurant') }}
                        </button>
                        <button @click="activeTab = 'notifications'" 
                                :class="activeTab === 'notifications' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            {{ __('settings.tabs.notifications') }}
                        </button>
                        <button @click="activeTab = 'integration'" 
                                :class="activeTab === 'integration' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                            </svg>
                            {{ __('settings.tabs.integration') }}
                        </button>
                        <button @click="activeTab = 'security'" 
                                :class="activeTab === 'security' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            {{ __('settings.tabs.security') }}
                        </button>
                        <button @click="activeTab = 'items'" 
                                :class="activeTab === 'items' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                            </svg>
                            {{ __('settings.tabs.items') }}
                        </button>
                        <button @click="activeTab = 'users'" 
                                :class="activeTab === 'users' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                                class="whitespace-nowrap py-4 px-6 border-b-2 font-medium text-sm">
                            <svg class="inline-block mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                            </svg>
                            {{ __('settings.tabs.users') }}
                        </button>
                    </nav>
                </div>

                <!-- Tab Content -->
                <div class="p-6 space-y-6">
                    <div x-show="['general','restaurant','notifications','integration','security'].includes(activeTab)" x-cloak>
                        <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="active_tab" x-model="activeTab">

                            <!-- General Settings -->
                            <div x-show="activeTab === 'general'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('settings.sections.general') }}</h3>
                            
                            @php
                                $currentTimezone = old('timezone', $generalSettings->timezone ?? 'America/Los_Angeles');
                                $currentCurrency = old('currency', $generalSettings->currency ?? currency_code());
                                $currentDateFormat = old('date_format', $generalSettings->date_format ?? 'm/d/Y');
                            @endphp

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="timezone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Timezone
                                    </label>
                                    <select name="timezone" id="timezone" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="America/New_York" @selected($currentTimezone === 'America/New_York')>Eastern Time (ET)</option>
                                        <option value="America/Chicago" @selected($currentTimezone === 'America/Chicago')>Central Time (CT)</option>
                                        <option value="America/Denver" @selected($currentTimezone === 'America/Denver')>Mountain Time (MT)</option>
                                        <option value="America/Los_Angeles" @selected($currentTimezone === 'America/Los_Angeles')>Pacific Time (PT)</option>
                                        <option value="UTC" @selected($currentTimezone === 'UTC')>UTC</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="currency" class="block text-sm font-medium text-gray-700 mb-2">
                                        Currency
                                    </label>
                                    <select name="currency" id="currency" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        @foreach(($supportedCurrencies ?? []) as $code => $data)
                                            <option value="{{ $code }}" @selected($currentCurrency === $code)>{{ currency_label($code) }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="date_format" class="block text-sm font-medium text-gray-700 mb-2">
                                        Date Format
                                    </label>
                                    <select name="date_format" id="date_format" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        <option value="m/d/Y" @selected($currentDateFormat === 'm/d/Y')>MM/DD/YYYY</option>
                                        <option value="d/m/Y" @selected($currentDateFormat === 'd/m/Y')>DD/MM/YYYY</option>
                                        <option value="Y-m-d" @selected($currentDateFormat === 'Y-m-d')>YYYY-MM-DD</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="language" class="block text-sm font-medium text-gray-700 mb-2">
                                        {{ __('settings.fields.language') }}
                                    </label>
                                    @php
                                        $availableLocales = $supportedLocales ?? supported_locales();
                                        $defaultLocale = $activeLocale['code'] ?? app()->getLocale();
                                        $selectedLanguage = old('language', $generalSettings->language ?? $defaultLocale);
                                        $currentLanguage = array_key_exists($selectedLanguage, $availableLocales) ? $selectedLanguage : $defaultLocale;
                                    @endphp
                                    <select name="language" id="language" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                        @foreach($availableLocales as $localeCode => $localeMeta)
                                            <option value="{{ $localeCode }}" @selected($currentLanguage === $localeCode)>
                                                {{ $localeMeta['label'] ?? Str::upper($localeCode) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            </div>

                            <!-- Restaurant Info -->
                            <div x-show="activeTab === 'restaurant'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('settings.sections.restaurant') }}</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="md:col-span-2">
                                    <label for="restaurant_name" class="block text-sm font-medium text-gray-700 mb-2">
                                        Restaurant Name
                                    </label>
                                    <input type="text" name="restaurant_name" id="restaurant_name"
                                           value="{{ old('restaurant_name', $profileSettings->restaurant_name) }}"
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div class="md:col-span-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700 mb-2">
                                        Address
                                    </label>
                                    <input type="text" name="address" id="address"
                                           value="{{ old('address', $profileSettings->address) }}"
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="city" class="block text-sm font-medium text-gray-700 mb-2">
                                        City
                                    </label>
                                    <input type="text" name="city" id="city"
                                           value="{{ old('city', $profileSettings->city) }}"
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="state" class="block text-sm font-medium text-gray-700 mb-2">
                                        State/Province
                                    </label>
                                    <input type="text" name="state" id="state"
                                           value="{{ old('state', $profileSettings->state) }}"
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="zip" class="block text-sm font-medium text-gray-700 mb-2">
                                        ZIP/Postal Code
                                    </label>
                                    <input type="text" name="zip" id="zip"
                                           value="{{ old('zip', $profileSettings->zip) }}"
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                        Phone Number
                                    </label>
                                    <input type="tel" name="phone" id="phone"
                                           value="{{ old('phone', $profileSettings->phone) }}"
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                        Email Address
                                    </label>
                                    <input type="email" name="email" id="email"
                                           value="{{ old('email', $profileSettings->email) }}"
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                <div>
                                    <label for="website" class="block text-sm font-medium text-gray-700 mb-2">
                                        Website
                                    </label>
                                    <input type="url" name="website" id="website"
                                           value="{{ old('website', $profileSettings->website) }}"
                                           class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                </div>
                            </div>
                            </div>

                            <!-- Notifications -->
                            <div x-show="activeTab === 'notifications'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('settings.sections.notifications') }}</h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="notify_requisitions" name="notify_requisitions" type="checkbox"
                                               @checked(old('notify_requisitions', $notificationSettings->notify_requisitions))
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="notify_requisitions" class="font-medium text-gray-700">Requisition Notifications</label>
                                        <p class="text-gray-500">Receive notifications when new requisitions are submitted or approved</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="notify_expenses" name="notify_expenses" type="checkbox"
                                               @checked(old('notify_expenses', $notificationSettings->notify_expenses))
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="notify_expenses" class="font-medium text-gray-700">Expense Notifications</label>
                                        <p class="text-gray-500">Get notified when expenses exceed budget thresholds</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="notify_purchase_orders" name="notify_purchase_orders" type="checkbox"
                                               @checked(old('notify_purchase_orders', $notificationSettings->notify_purchase_orders))
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
                                               @checked(old('notify_payroll', $notificationSettings->notify_payroll))
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
                                               @checked(old('notify_email_daily', $notificationSettings->notify_email_daily))
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="notify_email_daily" class="font-medium text-gray-700">Daily Summary Emails</label>
                                        <p class="text-gray-500">Receive a daily summary of all activities</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="sms_enabled" name="sms_enabled" type="checkbox"
                                               @checked(old('sms_enabled', $notificationSettings->sms_enabled))
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="sms_enabled" class="font-medium text-gray-700">SMS Alerts</label>
                                        <p class="text-gray-500">Send critical notifications via Twilio SMS.</p>
                                    </div>
                                </div>

                                <div class="flex items-start">
                                    <div class="flex items-center h-5">
                                        <input id="whatsapp_enabled" name="whatsapp_enabled" type="checkbox"
                                               @checked(old('whatsapp_enabled', $notificationSettings->whatsapp_enabled))
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="whatsapp_enabled" class="font-medium text-gray-700">WhatsApp Notifications</label>
                                        <p class="text-gray-500">Deliver alerts to your Twilio WhatsApp number.</p>
                                    </div>
                                </div>
                            </div>
                            </div>

                            <!-- Integrations -->
                            <div x-show="activeTab === 'integration'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('settings.sections.integration') }}</h3>

                            @php
                                $loyverseConnected = filled($integrationSettings->loyverse_api_key);
                                $smtpConfigured = filled($integrationSettings->smtp_host) && filled($integrationSettings->smtp_username);
                                $twilioConfigured = filled($integrationSettings->twilio_account_sid) && filled($integrationSettings->twilio_auth_token);
                            @endphp

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
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $loyverseConnected ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $loyverseConnected ? 'Connected' : 'Not Configured' }}
                                        </span>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <label for="loyverse_api_key" class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                                            <input type="password" name="loyverse_api_key" id="loyverse_api_key" 
                                                   value="{{ old('loyverse_api_key', $integrationSettings->loyverse_api_key) }}" 
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label class="flex items-center">
                                                <input type="checkbox" name="loyverse_auto_sync" 
                                                       @checked(old('loyverse_auto_sync', $integrationSettings->loyverse_auto_sync))
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
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $smtpConfigured ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $smtpConfigured ? 'Connected' : 'Not Configured' }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label for="smtp_host" class="block text-sm font-medium text-gray-700 mb-1">
                                                SMTP Host
                                            </label>
                                            <input type="text" name="smtp_host" id="smtp_host" 
                                                   value="{{ old('smtp_host', $integrationSettings->smtp_host) }}" 
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="smtp_port" class="block text-sm font-medium text-gray-700 mb-1">
                                                SMTP Port
                                            </label>
                                            <input type="text" name="smtp_port" id="smtp_port" 
                                                   value="{{ old('smtp_port', $integrationSettings->smtp_port) }}" 
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="smtp_username" class="block text-sm font-medium text-gray-700 mb-1">
                                                SMTP Username
                                            </label>
                                            <input type="text" name="smtp_username" id="smtp_username"
                                                   value="{{ old('smtp_username', $integrationSettings->smtp_username) }}"
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="smtp_password" class="block text-sm font-medium text-gray-700 mb-1">
                                                SMTP Password
                                            </label>
                                            <input type="password" name="smtp_password" id="smtp_password"
                                                   value="{{ old('smtp_password', $integrationSettings->smtp_password) }}"
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 mb-1">
                                                Encryption
                                            </label>
                                            <input type="text" name="smtp_encryption" id="smtp_encryption"
                                                   value="{{ old('smtp_encryption', $integrationSettings->smtp_encryption) }}"
                                                   placeholder="tls / ssl"
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                </div>

                                <!-- Twilio Integration -->
                                <div class="border border-gray-200 rounded-lg p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center">
                                            <div class="bg-green-100 rounded-lg p-2">
                                                <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h18M9 3v4M15 3v4m-9 4h12l-1.5 9h-9z"/>
                                                </svg>
                                            </div>
                                            <div class="ml-4">
                                                <h4 class="text-sm font-medium text-gray-900">Twilio Messaging</h4>
                                                <p class="text-sm text-gray-500">Manage SMS and WhatsApp delivery</p>
                                            </div>
                                        </div>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $twilioConfigured ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                            {{ $twilioConfigured ? 'Connected' : 'Not Configured' }}
                                        </span>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label for="twilio_account_sid" class="block text-sm font-medium text-gray-700 mb-1">
                                                Account SID
                                            </label>
                                            <input type="text" name="twilio_account_sid" id="twilio_account_sid"
                                                   value="{{ old('twilio_account_sid', $integrationSettings->twilio_account_sid) }}"
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="twilio_auth_token" class="block text-sm font-medium text-gray-700 mb-1">
                                                Auth Token
                                            </label>
                                            <input type="password" name="twilio_auth_token" id="twilio_auth_token"
                                                   value="{{ old('twilio_auth_token', $integrationSettings->twilio_auth_token) }}"
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="twilio_sms_number" class="block text-sm font-medium text-gray-700 mb-1">
                                                SMS Number
                                            </label>
                                            <input type="text" name="twilio_sms_number" id="twilio_sms_number"
                                                   value="{{ old('twilio_sms_number', $integrationSettings->twilio_sms_number) }}"
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                        <div>
                                            <label for="twilio_whatsapp_number" class="block text-sm font-medium text-gray-700 mb-1">
                                                WhatsApp Number
                                            </label>
                                            <input type="text" name="twilio_whatsapp_number" id="twilio_whatsapp_number"
                                                   value="{{ old('twilio_whatsapp_number', $integrationSettings->twilio_whatsapp_number) }}"
                                                   class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 text-sm">
                                        </div>
                                    </div>
                                    <div class="mt-3 space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" name="twilio_sms_enabled"
                                                   @checked(old('twilio_sms_enabled', $integrationSettings->twilio_sms_enabled))
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-gray-700">Enable SMS delivery</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="twilio_whatsapp_enabled"
                                                   @checked(old('twilio_whatsapp_enabled', $integrationSettings->twilio_whatsapp_enabled))
                                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-gray-700">Enable WhatsApp delivery</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Security -->
                        <div x-show="activeTab === 'security'" class="space-y-6">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('settings.sections.security') }}</h3>
                            
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
                                                     @checked(old('two_factor', $securitySettings->two_factor_enabled))
                                                     class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="two_factor" class="font-medium text-gray-700">Enable Two-Factor Authentication</label>
                                            <p class="text-gray-500">Add an extra layer of security to your account</p>
                                        </div>
                                    </div>

                                    <div class="flex items-start">
                                        <div class="flex items-center h-5">
                                                 <input id="session_timeout" name="session_timeout" type="checkbox"
                                                     @checked(old('session_timeout', $securitySettings->session_timeout_enabled))
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

                    <!-- Items Management -->
                    @php
                        $isEditingItem = filled($editingItem);
                        $itemFormAction = $isEditingItem
                            ? route('items.update', $editingItem)
                            : route('items.store');
                        $vendorNames = $vendors->where('is_active', true)->pluck('name')->filter()->unique()->sort()->values();
                        $activeCategories = $itemCategories->where('status', 'active')->values();

                        $itemModalShouldOpen = $isEditingItem || $errors->items->any();
                        $categoryModalShouldOpen = $errors->categories->any();
                        $vendorModalShouldOpen = $errors->vendors->any();
                    @endphp
                    <div
                        x-show="activeTab === 'items'"
                        x-cloak
                        class="space-y-6"
                        x-data="{
                            showItemModal: @js($itemModalShouldOpen),
                            showCategoryModal: @js($categoryModalShouldOpen),
                            showVendorModal: @js($vendorModalShouldOpen),
                            open(which) { this[which] = true },
                            close(which) { this[which] = false }
                        }"
                    >
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">Items</h3>
                                    <p class="mt-1 text-sm text-gray-600">Maintain the item catalog that powers requisitions, POs, and stock alerts.</p>
                                </div>
                                @if($isEditingItem)
                                    <a href="{{ route('settings', ['tab' => 'items']) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                        Exit Edit Mode
                                    </a>
                                @endif
                            </div>

                            @if ($errors->items->any())
                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    <ul class="list-disc space-y-1 pl-5">
                                        @foreach ($errors->items->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                                <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 bg-gray-50 px-6 py-4">
                                    <div class="space-y-1">
                                        <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                                            <span>{{ $items->count() }} items</span>
                                            <span aria-hidden="true">•</span>
                                            <span>{{ $vendorNames->count() }} vendors</span>
                                            @if($itemCategories->count() > 0)
                                                <span aria-hidden="true">•</span>
                                                <span>{{ $activeCategories->count() }} active categories</span>
                                            @endif
                                            @if($items->where('status', 'inactive')->count() > 0)
                                                <span aria-hidden="true">•</span>
                                                <span>{{ $items->where('status', 'inactive')->count() }} inactive</span>
                                            @endif
                                        </div>
                                        <a href="{{ route('items.index') }}" class="inline-flex text-sm font-medium text-blue-600 hover:text-blue-700">Open full items view →</a>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" @click.prevent="open('showItemModal')" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                            </svg>
                                            Add New Item
                                        </button>
                                        <button type="button" @click.prevent="open('showCategoryModal')" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                            <svg class="h-4 w-4 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M4 3a2 2 0 00-2 2v1a2 2 0 002 2h1v6a2 2 0 002 2h6v1a2 2 0 002 2h1a2 2 0 002-2v-1a2 2 0 00-2-2h-1V8a2 2 0 00-2-2H8V5a2 2 0 00-2-2H4z" />
                                            </svg>
                                            Add New Category
                                        </button>
                                        <button type="button" @click.prevent="open('showVendorModal')" class="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                            <svg class="h-4 w-4 text-gray-500" viewBox="0 0 24 24" fill="currentColor">
                                                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v1a1 1 0 001 1h14a1 1 0 001-1v-1c0-2.66-5.33-4-8-4z" />
                                            </svg>
                                            Add New Vendor
                                        </button>
                                    </div>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-6 py-3 text-left">Item</th>
                                                <th class="px-6 py-3 text-left">Vendor</th>
                                                <th class="px-6 py-3 text-left">Price</th>
                                                <th class="px-6 py-3 text-left">Stock</th>
                                                <th class="px-6 py-3 text-left">Status</th>
                                                <th class="px-6 py-3 text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @forelse ($items as $item)
                                                @php
                                                    $isLowStock = !is_null($item->reorder_level) && !is_null($item->stock) && $item->stock <= $item->reorder_level;
                                                    $isInactive = $item->status !== 'active';
                                                @endphp
                                                <tr class="{{ $isEditingItem && $editingItem->id === $item->id ? 'bg-blue-50/50' : '' }}">
                                                    <td class="px-6 py-4 align-top">
                                                        <div class="font-medium text-gray-900">{{ $item->name }}</div>
                                                        <div class="mt-1 text-xs uppercase tracking-wide text-gray-500">{{ $item->category ?? 'Uncategorized' }} &middot; {{ $item->uom }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 align-top text-gray-600">{{ $item->vendor }}</td>
                                                    <td class="px-6 py-4 align-top font-semibold text-gray-900">{{ currency_format($item->price) }}</td>
                                                    <td class="px-6 py-4 align-top text-gray-700">
                                                        @if(!is_null($item->stock))
                                                            <span class="{{ $isLowStock ? 'text-red-600 font-semibold' : '' }}">{{ rtrim(rtrim(number_format((float) $item->stock, 2), '0'), '.') }}</span>
                                                            @if($isLowStock)
                                                                <span class="ml-2 inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">Low</span>
                                                            @endif
                                                        @else
                                                            <span class="text-gray-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 align-top">
                                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $isInactive ? 'bg-gray-200 text-gray-700' : 'bg-green-100 text-green-700' }}">
                                                            {{ Str::ucfirst($item->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 align-top">
                                                        <div class="flex items-center justify-end gap-3 text-sm">
                                                            <a href="{{ route('settings', ['tab' => 'items', 'edit_item' => $item->id]) }}" class="font-medium text-blue-600 hover:text-blue-700">Edit</a>
                                                            <form action="{{ route('items.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete {{ $item->name }}? This cannot be undone.');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="font-medium text-red-600 hover:text-red-700">Delete</button>
                                                            </form>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No items yet. Add your first item to get started.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Item Modal -->
                            <div
                                x-cloak
                                x-show="showItemModal"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @keydown.escape.window="close('showItemModal')"
                                class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4 py-6"
                                @click.self="close('showItemModal')"
                            >
                                <div class="w-full max-w-3xl overflow-hidden rounded-lg bg-white shadow-2xl">
                                    <div class="flex items-start justify-between border-b border-gray-200 px-6 py-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">{{ $isEditingItem ? 'Edit Item' : 'Add New Item' }}</h3>
                                            <p class="mt-1 text-sm text-gray-500">Fields with <span class="text-red-500">*</span> are required.</p>
                                        </div>
                                        <button type="button" class="text-gray-400 hover:text-gray-600" @click="close('showItemModal')">
                                            <span class="sr-only">Close</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="max-h-[80vh] overflow-y-auto px-6 py-6">
                                        @if ($errors->items->any())
                                            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                                <ul class="list-disc space-y-1 pl-5">
                                                    @foreach ($errors->items->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <form action="{{ $itemFormAction }}" method="POST" class="space-y-4">
                                            @csrf
                                            @if ($isEditingItem)
                                                @method('PUT')
                                            @endif

                                            <div class="grid gap-4 sm:grid-cols-2">
                                                <div class="sm:col-span-2">
                                                    <label for="item-name" class="block text-sm font-medium text-gray-700">Item name <span class="text-red-500">*</span></label>
                                                    <input id="item-name" name="name" type="text" value="{{ old('name', $editingItem->name ?? '') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <div>
                                                    <label for="item-category" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                                                    <input id="item-category" name="category" list="item-categories" value="{{ old('category', $editingItem->category ?? '') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                    <datalist id="item-categories">
                                                        @foreach ($itemCategories->where('status', 'active') as $category)
                                                            <option value="{{ $category->name }}"></option>
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                                <div>
                                                    <label for="item-uom" class="block text-sm font-medium text-gray-700">Unit of measure <span class="text-red-500">*</span></label>
                                                    @php
                                                        $selectedUom = old('uom', $editingItem->uom ?? '');
                                                        $normalizedUnits = collect($unitOptions)->map(fn ($u) => Str::lower($u));
                                                    @endphp
                                                    <select id="item-uom" name="uom" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                        <option value="" disabled @selected($selectedUom === '')>Select unit</option>
                                                        @foreach ($unitOptions as $unit)
                                                            <option value="{{ $unit }}" @selected(Str::lower($selectedUom) === Str::lower($unit))>{{ Str::upper($unit) }}</option>
                                                        @endforeach
                                                        @if ($selectedUom !== '' && !$normalizedUnits->contains(Str::lower($selectedUom)))
                                                            <option value="{{ $selectedUom }}" selected>{{ Str::upper($selectedUom) }}</option>
                                                        @endif
                                                    </select>
                                                </div>
                                                <div>
                                                    <label for="item-vendor" class="block text-sm font-medium text-gray-700">Vendor/Supplier <span class="text-red-500">*</span></label>
                                                    <input id="item-vendor" name="vendor" list="item-vendors" value="{{ old('vendor', $editingItem->vendor ?? '') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                    <datalist id="item-vendors">
                                                        @foreach ($vendorNames as $vendorName)
                                                            <option value="{{ $vendorName }}"></option>
                                                        @endforeach
                                                    </datalist>
                                                </div>
                                                <div>
                                                    <label for="item-price" class="block text-sm font-medium text-gray-700">Price ({{ currency_label() }}) <span class="text-red-500">*</span></label>
                                                    <input id="item-price" name="price" type="number" min="0" step="0.01" value="{{ old('price', $editingItem->price ?? '') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <div>
                                                    <label for="item-status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                                    <select id="item-status" name="status" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                        <option value="active" @selected(old('status', $editingItem->status ?? 'active') === 'active')>Active</option>
                                                        <option value="inactive" @selected(old('status', $editingItem->status ?? 'active') === 'inactive')>Inactive</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label for="item-stock" class="block text-sm font-medium text-gray-700">Current stock</label>
                                                    <input id="item-stock" name="stock" type="number" min="0" step="0.01" value="{{ old('stock', $editingItem->stock ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <div>
                                                    <label for="item-reorder" class="block text-sm font-medium text-gray-700">Reorder level</label>
                                                    <input id="item-reorder" name="reorder_level" type="number" min="0" step="0.01" value="{{ old('reorder_level', $editingItem->reorder_level ?? '') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label for="item-description" class="block text-sm font-medium text-gray-700">Description</label>
                                                    <textarea id="item-description" name="description" rows="3" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">{{ old('description', $editingItem->description ?? '') }}</textarea>
                                                </div>
                                            </div>

                                            <div class="rounded-md bg-blue-50 px-3 py-3 text-xs text-blue-700">
                                                Keep vendor and pricing details up to date so requisitions always reflect current costs.
                                            </div>

                                            <div class="flex items-center justify-end gap-3">
                                                <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-700" @click="close('showItemModal')">Cancel</button>
                                                <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                                    {{ $isEditingItem ? 'Save Item' : 'Create Item' }}
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Category Modal -->
                            <div
                                x-cloak
                                x-show="showCategoryModal"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @keydown.escape.window="close('showCategoryModal')"
                                class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4 py-6"
                                @click.self="close('showCategoryModal')"
                            >
                                <div class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-2xl">
                                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">Create Category</h3>
                                            <p class="mt-1 text-sm text-gray-500">Add reusable categories for your item catalog.</p>
                                        </div>
                                        <button type="button" class="text-gray-400 hover:text-gray-600" @click="close('showCategoryModal')">
                                            <span class="sr-only">Close</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="px-6 py-6">
                                        @if ($errors->categories->any())
                                            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                                                <ul class="list-disc space-y-1 pl-4">
                                                    @foreach ($errors->categories->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <form action="{{ route('item-categories.store') }}" method="POST" class="space-y-4">
                                            @csrf
                                            <div>
                                                <label for="category-name" class="block text-sm font-medium text-gray-700">Category Name <span class="text-red-500">*</span></label>
                                                <input id="category-name" name="category_name" type="text" value="{{ old('category_name') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label for="category-status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                                <select id="category-status" name="category_status" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                    @php $categoryStatus = old('category_status', 'active'); @endphp
                                                    <option value="active" @selected($categoryStatus === 'active')>Active</option>
                                                    <option value="inactive" @selected($categoryStatus === 'inactive')>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="flex items-center justify-end gap-3">
                                                <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-700" @click="close('showCategoryModal')">Cancel</button>
                                                <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                                    Save Category
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Vendor Modal -->
                            <div
                                x-cloak
                                x-show="showVendorModal"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @keydown.escape.window="close('showVendorModal')"
                                class="fixed inset-0 z-40 flex items-center justify-center bg-gray-900/60 px-4 py-6"
                                @click.self="close('showVendorModal')"
                            >
                                <div class="w-full max-w-md overflow-hidden rounded-lg bg-white shadow-2xl">
                                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-900">Create Vendor</h3>
                                            <p class="mt-1 text-sm text-gray-500">Keep vendor contact details handy for requisitions and POs.</p>
                                        </div>
                                        <button type="button" class="text-gray-400 hover:text-gray-600" @click="close('showVendorModal')">
                                            <span class="sr-only">Close</span>
                                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="px-6 py-6">
                                        @if ($errors->vendors->any())
                                            <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                                                <ul class="list-disc space-y-1 pl-4">
                                                    @foreach ($errors->vendors->all() as $error)
                                                        <li>{{ $error }}</li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif

                                        <form action="{{ route('vendors.store') }}" method="POST" class="space-y-4">
                                            @csrf
                                            <div>
                                                <label for="vendor-name" class="block text-sm font-medium text-gray-700">Vendor Name <span class="text-red-500">*</span></label>
                                                <input id="vendor-name" name="vendor_name" type="text" value="{{ old('vendor_name') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label for="vendor-phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                                <input id="vendor-phone" name="vendor_phone" type="text" value="{{ old('vendor_phone') }}" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="e.g. +255 700 000 000">
                                            </div>
                                            <div>
                                                <label for="vendor-status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                                @php $vendorStatus = old('vendor_status', 'active'); @endphp
                                                <select id="vendor-status" name="vendor_status" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                    <option value="active" @selected($vendorStatus === 'active')>Active</option>
                                                    <option value="inactive" @selected($vendorStatus === 'inactive')>Inactive</option>
                                                </select>
                                            </div>
                                            <div class="flex items-center justify-end gap-3">
                                                <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-700" @click="close('showVendorModal')">Cancel</button>
                                                <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                                    Save Vendor
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                    <!-- User Management -->
                    <div x-show="activeTab === 'users'" x-cloak class="space-y-6">
                            @php
                                $isAdmin = auth()->user()->hasRole('admin');
                                $roleDisplayName = static fn (string $role): string => Str::title(str_replace(['_', '-'], ' ', $role));
                            @endphp

                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900">User Management</h3>
                                    <p class="mt-1 text-sm text-gray-600">Invite teammates, assign roles, and resend onboarding emails.</p>
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $users->count() }} users &middot; {{ $roles->count() }} roles available
                                </div>
                            </div>

                            @if ($errors->invite->any())
                                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                    <h4 class="font-semibold">Invitation could not be sent:</h4>
                                    <ul class="mt-2 list-disc space-y-1 pl-5">
                                        @foreach ($errors->invite->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ($errors->roles->any())
                                <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                                    <h4 class="font-semibold">Role update issue</h4>
                                    <p class="mt-1">{{ $errors->roles->first() }}</p>
                                </div>
                            @endif

                            <div class="grid gap-6 lg:grid-cols-3">
                                @if($isAdmin)
                                    <div class="lg:col-span-1">
                                        <div class="rounded-lg border border-gray-200 p-6 shadow-sm">
                                            <h4 class="text-base font-semibold text-gray-900">Invite a user</h4>
                                            <p class="mt-1 text-xs text-gray-500">We will email a password setup link immediately.</p>

                                            <form action="{{ route('admin.users.invite') }}" method="POST" class="mt-4 space-y-4">
                                                @csrf

                                                <div>
                                                    <label for="invite-name" class="block text-sm font-medium text-gray-700">Full name</label>
                                                    <input id="invite-name" name="name" type="text" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>

                                                <div>
                                                    <label for="invite-email" class="block text-sm font-medium text-gray-700">Email address</label>
                                                    <input id="invite-email" name="email" type="email" value="{{ old('email') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                </div>

                                                <div>
                                                    <span class="block text-sm font-medium text-gray-700">Assign roles</span>
                                                    <div class="mt-2 grid grid-cols-1 gap-2">
                                                        @foreach ($roles as $role)
                                                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                                                <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(collect(old('roles', []))->contains($role->name)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                <span>{{ $roleDisplayName($role->name) }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>

                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                    <input type="checkbox" name="send_reset" value="1" @checked(old('send_reset', true)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                    Send password setup email now
                                                </label>

                                                <button type="submit" class="inline-flex w-full items-center justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                                    Send Invitation
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endif

                                <div class="{{ $isAdmin ? 'lg:col-span-2' : 'lg:col-span-3' }} space-y-4">
                                    @forelse ($users as $user)
                                        @php
                                            $currentRoles = $user->roles->pluck('name')->all();
                                            $oldRoles = $currentRoles;
                                            if (old('_target_user_id') && (int) old('_target_user_id') === $user->id) {
                                                $oldRoles = old('roles', $currentRoles);
                                            }
                                        @endphp
                                        <div class="rounded-lg border border-gray-200 p-5 shadow-sm">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-900">{{ $user->name }}</div>
                                                    <div class="text-sm text-gray-500">{{ $user->email }}</div>
                                                </div>
                                                <div class="flex flex-wrap gap-2">
                                                    @forelse ($user->roles as $role)
                                                        <span class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700">{{ $roleDisplayName($role->name) }}</span>
                                                    @empty
                                                        <span class="text-xs italic text-gray-500">No roles assigned</span>
                                                    @endforelse
                                                </div>
                                            </div>

                                            <div class="mt-4 flex flex-wrap items-center gap-3 text-xs text-gray-500">
                                                <span>Joined {{ optional($user->created_at)->format('M d, Y') ?? '—' }}</span>
                                                <span aria-hidden="true">•</span>
                                                <span>Last login {{ optional($user->last_login_at)->diffForHumans() ?? '—' }}</span>
                                            </div>

                                            @if($isAdmin)
                                                @if(auth()->id() === $user->id)
                                                    <div class="mt-4 rounded-md bg-gray-50 px-3 py-3 text-xs text-gray-500">
                                                        You are viewing your account. Another admin must update your roles.
                                                    </div>
                                                @else
                                                    <div class="mt-4 space-y-4 border-t border-gray-200 pt-4">
                                                        <form action="{{ route('admin.users.roles.update', $user) }}" method="POST" class="space-y-3">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="_target_user_id" value="{{ $user->id }}">
                                                            <span class="text-sm font-medium text-gray-700">Update roles</span>
                                                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                                @foreach ($roles as $role)
                                                                    <label class="flex items-start gap-2 text-sm text-gray-700">
                                                                        <input type="checkbox" name="roles[]" value="{{ $role->name }}" @checked(in_array($role->name, $oldRoles ?? [])) class="mt-1 h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        <span>{{ $roleDisplayName($role->name) }}</span>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                            <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                                                Save Roles
                                                            </button>
                                                        </form>

                                                        <form action="{{ route('admin.users.resend-invite', $user) }}" method="POST" class="inline-flex">
                                                            @csrf
                                                            <button type="submit" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                                                                Resend invitation email
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    @empty
                                        <div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">
                                            No users found for this entity yet.
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                        <div class="rounded-lg bg-gray-50 p-4 text-sm text-gray-600">
                            <h4 class="font-medium text-gray-900">Role overview</h4>
                            <p class="mt-2">Assign at least one administrator so there is always someone who can manage access and invitations.</p>
                        </div>
                    </div>
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
</div>
@endsection

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush
