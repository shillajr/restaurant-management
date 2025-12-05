@extends('layouts.app')

@section('title', __('settings.title'))

@php
    $businessSectionsMeta = [
        'general' => [
            'label' => 'General',
            'summary' => 'Locale, currency, and time',
        ],
        'restaurant' => [
            'label' => 'Restaurant Info',
            'summary' => 'Brand and contact details',
        ],
        'notifications' => [
            'label' => 'Notifications',
            'summary' => 'Alerts across channels',
        ],
    ];

    $productSectionsMeta = [
        'vendors' => [
            'label' => 'Vendors',
            'summary' => 'Supplier directory and status',
        ],
        'items' => [
            'label' => 'Items',
            'summary' => 'Catalog powering requisitions',
        ],
        'categories' => [
            'label' => 'Categories',
            'summary' => 'Grouping for reporting',
        ],
    ];

    $tabMeta = [
        'business' => [
            'label' => 'Business',
            'description' => 'Company profile and preferences',
        ],
        'products' => [
            'label' => 'Products',
            'description' => 'Vendors, items, and categories',
        ],
        'integration' => [
            'label' => 'Integrations',
            'description' => 'Connect external systems',
        ],
        'security' => [
            'label' => 'Security',
            'description' => 'Policies and controls',
        ],
        'users' => [
            'label' => 'Users',
            'description' => 'Manage team access',
        ],
    ];

    $timezoneOptions = [
        'America/New_York' => 'Eastern Time (ET)',
        'America/Chicago' => 'Central Time (CT)',
        'America/Denver' => 'Mountain Time (MT)',
        'America/Los_Angeles' => 'Pacific Time (PT)',
        'UTC' => 'UTC',
    ];

    $dateFormatOptions = [
        'm/d/Y' => 'MM/DD/YYYY',
        'd/m/Y' => 'DD/MM/YYYY',
        'Y-m-d' => 'YYYY-MM-DD',
    ];

    $currentTimezone = old('timezone', $generalSettings->timezone ?? config('app.timezone'));
    $currentCurrency = old('currency', $generalSettings->currency ?? 'USD');
    $currentDateFormat = old('date_format', $generalSettings->date_format ?? 'm/d/Y');
    $currentLanguage = old('language', $generalSettings->language ?? 'en');

    $currencyOptions = collect($supportedCurrencies)->mapWithKeys(function ($currency, $code) {
        if (is_int($code)) {
            $stringValue = is_array($currency) ? ($currency['code'] ?? reset($currency)) : $currency;
            return [$stringValue => (string) $stringValue];
        }

        if (is_array($currency)) {
            $name = (string) ($currency['name'] ?? $code);
            return [$code => sprintf('%s — %s', $code, $name)];
        }

        return [$code => (string) $currency];
    });

    $localeOptions = collect($supportedLocales)->mapWithKeys(function ($locale, $code) {
        if (is_int($code)) {
            $stringValue = is_array($locale) ? ($locale['code'] ?? reset($locale)) : $locale;
            return [$stringValue => (string) $stringValue];
        }

        if (is_array($locale)) {
            $label = (string) ($locale['label'] ?? $code);
            $native = (string) ($locale['native'] ?? '');
            $display = trim($native) !== '' && strcasecmp($label, $native) !== 0
                ? sprintf('%s — %s', $label, $native)
                : $label;

            return [$code => $display];
        }

        return [$code => (string) $locale];
    });

    $isEditingVendor = filled($editingVendor);
    $vendorModalShouldOpen = $isEditingVendor || $errors->vendors->any();

    $isEditingItem = filled($editingItem);
    $itemFormAction = $isEditingItem ? route('items.update', $editingItem) : route('items.store');
    $activeVendors = $vendors->where('is_active', true)->values();
    $selectedVendorId = old('vendor_id');
    if (! $selectedVendorId && $isEditingItem) {
        $matchedVendor = $vendors->firstWhere('name', $editingItem->vendor);
        $selectedVendorId = $matchedVendor?->id;
    }
    $activeCategories = $itemCategories->where('status', 'active')->values();
    $categoryStats = [
        'total' => $itemCategories->count(),
        'active' => $activeCategories->count(),
        'inactive' => $itemCategories->where('status', 'inactive')->count(),
    ];
    $itemModalShouldOpen = $isEditingItem || $errors->items->any();
    $categoryModalShouldOpen = $errors->categories->any();
@endphp

@section('content')
<div class="px-4 py-8 sm:px-6 lg:px-10">
    <div class="mx-auto max-w-6xl space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Settings</h1>
                <p class="mt-2 text-sm text-gray-600">Tune company preferences, manage the catalog, and keep your team aligned.</p>
            </div>
            <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to dashboard
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('info'))
            <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                {{ session('info') }}
            </div>
        @endif

        <div
            x-data="{
                activeTab: @js($activeTab),
                businessSection: @js($businessSection),
                productSection: @js($productSection),
                showVendorModal: @js($vendorModalShouldOpen),
                showItemModal: @js($itemModalShouldOpen),
                showCategoryModal: @js($categoryModalShouldOpen),
                open(which) { this[which] = true },
                close(which) { this[which] = false },
                goToBusiness(section) { this.activeTab = 'business'; this.businessSection = section },
                goToProduct(section) { this.activeTab = 'products'; this.productSection = section }
            }"
            class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-200"
        >
            <div class="border-b border-gray-200 px-4 py-4 sm:px-6">
                <nav class="flex gap-2 overflow-x-auto" aria-label="Settings tabs">
                    @foreach ($tabMeta as $key => $meta)
                        <button
                            type="button"
                            @click="activeTab = '{{ $key }}'"
                            class="flex min-w-[10rem] flex-col rounded-xl border px-4 py-3 text-left text-sm transition"
                            :class="activeTab === '{{ $key }}' ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-blue-200 hover:text-blue-600'"
                        >
                            <span class="font-semibold">{{ $meta['label'] }}</span>
                            <span class="mt-1 text-xs text-gray-500" :class="activeTab === '{{ $key }}' ? 'text-blue-600' : ''">{{ $meta['description'] }}</span>
                        </button>
                    @endforeach
                </nav>
            </div>

            <div class="space-y-10 px-4 py-6 sm:px-6">
                <div
                    x-show="activeTab === 'business'"
                    x-cloak
                    class="space-y-6"
                >
                    <div class="overflow-hidden rounded-2xl border border-gray-200">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-xl font-semibold text-gray-900">Business settings</h2>
                            <p class="mt-1 text-sm text-gray-500">Keep company information and notification preferences up to date.</p>
                        </div>
                        <div class="space-y-6 p-6">
                            <div class="flex flex-wrap gap-2">
                                @foreach ($businessSectionsMeta as $sectionKey => $meta)
                                    <button
                                        type="button"
                                        @click="businessSection = '{{ $sectionKey }}'"
                                        class="flex flex-col rounded-xl border px-4 py-3 text-left text-sm transition"
                                        :class="businessSection === '{{ $sectionKey }}' ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-blue-200 hover:text-blue-600'"
                                    >
                                        <span class="font-semibold">{{ $meta['label'] }}</span>
                                        <span class="mt-1 text-xs text-gray-500" :class="businessSection === '{{ $sectionKey }}' ? 'text-blue-600' : ''">{{ $meta['summary'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <form action="{{ route('settings.update') }}" method="POST" class="space-y-8">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="active_tab" :value="businessSection">

                                <div x-show="businessSection === 'general'" x-cloak class="space-y-6">
                                    <h3 class="text-lg font-medium text-gray-900">General settings</h3>
                                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                        <div>
                                            <label for="timezone" class="mb-2 block text-sm font-medium text-gray-700">Timezone</label>
                                            <select name="timezone" id="timezone" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                @foreach ($timezoneOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($currentTimezone === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="currency" class="mb-2 block text-sm font-medium text-gray-700">Currency</label>
                                            <select name="currency" id="currency" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                @foreach ($currencyOptions as $code => $label)
                                                    <option value="{{ $code }}" @selected($currentCurrency === $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="date_format" class="mb-2 block text-sm font-medium text-gray-700">Date format</label>
                                            <select name="date_format" id="date_format" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                @foreach ($dateFormatOptions as $value => $label)
                                                    <option value="{{ $value }}" @selected($currentDateFormat === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div>
                                            <label for="language" class="mb-2 block text-sm font-medium text-gray-700">Language</label>
                                            <select name="language" id="language" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                @foreach ($localeOptions as $code => $label)
                                                    <option value="{{ $code }}" @selected($currentLanguage === $code)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    <div class="rounded-md border border-blue-100 bg-blue-50 px-3 py-3 text-xs text-blue-700">
                                        These preferences control regional formatting, currency display, and the default experience across the platform.
                                    </div>
                                </div>

                                <div x-show="businessSection === 'restaurant'" x-cloak class="space-y-6">
                                    <h3 class="text-lg font-medium text-gray-900">Restaurant information</h3>
                                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                        <div class="sm:col-span-2">
                                            <label for="restaurant_name" class="mb-2 block text-sm font-medium text-gray-700">Restaurant name</label>
                                            <input type="text" name="restaurant_name" id="restaurant_name" value="{{ old('restaurant_name', $profileSettings->restaurant_name) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div class="sm:col-span-2">
                                            <label for="address" class="mb-2 block text-sm font-medium text-gray-700">Address</label>
                                            <input type="text" name="address" id="address" value="{{ old('address', $profileSettings->address) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label for="city" class="mb-2 block text-sm font-medium text-gray-700">City</label>
                                            <input type="text" name="city" id="city" value="{{ old('city', $profileSettings->city) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label for="state" class="mb-2 block text-sm font-medium text-gray-700">State/Province</label>
                                            <input type="text" name="state" id="state" value="{{ old('state', $profileSettings->state) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label for="zip" class="mb-2 block text-sm font-medium text-gray-700">ZIP/Postal code</label>
                                            <input type="text" name="zip" id="zip" value="{{ old('zip', $profileSettings->zip) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label for="phone" class="mb-2 block text-sm font-medium text-gray-700">Phone number</label>
                                            <input type="tel" name="phone" id="phone" value="{{ old('phone', $profileSettings->phone) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label for="email" class="mb-2 block text-sm font-medium text-gray-700">Email address</label>
                                            <input type="email" name="email" id="email" value="{{ old('email', $profileSettings->email) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                        <div>
                                            <label for="website" class="mb-2 block text-sm font-medium text-gray-700">Website</label>
                                            <input type="url" name="website" id="website" value="{{ old('website', $profileSettings->website) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        </div>
                                    </div>

                                    <div class="rounded-md border border-gray-200 bg-gray-50 px-3 py-3 text-xs text-gray-600">
                                        Keep these details accurate so purchase orders, invoices, and outbound communications reflect the right information.
                                    </div>
                                </div>

                                <div x-show="businessSection === 'notifications'" x-cloak class="space-y-6">
                                    <h3 class="text-lg font-medium text-gray-900">{{ __('settings.sections.notifications') }}</h3>

                                    <div class="space-y-4">
                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="notify_requisitions" name="notify_requisitions" type="checkbox" @checked(old('notify_requisitions', $notificationSettings->notify_requisitions)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="notify_requisitions" class="font-medium text-gray-700">Requisition notifications</label>
                                                <p class="text-gray-500">Receive notifications when new requisitions are submitted or approved.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="notify_expenses" name="notify_expenses" type="checkbox" @checked(old('notify_expenses', $notificationSettings->notify_expenses)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="notify_expenses" class="font-medium text-gray-700">Expense notifications</label>
                                                <p class="text-gray-500">Get notified when expenses exceed budget thresholds.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="notify_purchase_orders" name="notify_purchase_orders" type="checkbox" @checked(old('notify_purchase_orders', $notificationSettings->notify_purchase_orders)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="notify_purchase_orders" class="font-medium text-gray-700">Purchase order updates</label>
                                                <p class="text-gray-500">Receive updates on purchase order status changes.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="notify_payroll" name="notify_payroll" type="checkbox" @checked(old('notify_payroll', $notificationSettings->notify_payroll)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="notify_payroll" class="font-medium text-gray-700">Payroll reminders</label>
                                                <p class="text-gray-500">Get reminders for upcoming payroll processing dates.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="notify_email_daily" name="notify_email_daily" type="checkbox" @checked(old('notify_email_daily', $notificationSettings->notify_email_daily)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="notify_email_daily" class="font-medium text-gray-700">Daily summary emails</label>
                                                <p class="text-gray-500">Receive a daily summary of all activities.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="sms_enabled" name="sms_enabled" type="checkbox" @checked(old('sms_enabled', $notificationSettings->sms_enabled)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="sms_enabled" class="font-medium text-gray-700">SMS alerts</label>
                                                <p class="text-gray-500">Send critical notifications via Twilio SMS.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="whatsapp_enabled" name="whatsapp_enabled" type="checkbox" @checked(old('whatsapp_enabled', $notificationSettings->whatsapp_enabled)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="whatsapp_enabled" class="font-medium text-gray-700">WhatsApp notifications</label>
                                                <p class="text-gray-500">Deliver alerts to your Twilio WhatsApp number.</p>
                                            </div>
                                        </div>
                                    </div>

                                    @php
                                        $onboardingTemplateValue = old('user_onboarding_sms_template', $notificationSettings->user_onboarding_sms_template ?? $userOnboardingDefaultTemplate);
                                    @endphp

                                    <div class="space-y-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-800">Employee onboarding SMS</h4>
                                                <p class="text-xs text-gray-600">Automatically text new team members their login credentials after you create their accounts.</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <label for="user_onboarding_sms_enabled" class="text-sm font-medium text-gray-700">Send welcome SMS</label>
                                                <input id="user_onboarding_sms_enabled" name="user_onboarding_sms_enabled" type="checkbox" @checked(old('user_onboarding_sms_enabled', $notificationSettings->user_onboarding_sms_enabled)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                            <div class="space-y-2 lg:col-span-2">
                                                <label for="user_onboarding_sms_template" class="block text-xs font-medium text-gray-600">Welcome message</label>
                                                <textarea id="user_onboarding_sms_template" name="user_onboarding_sms_template" rows="4" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ $onboardingTemplateValue }}</textarea>
                                                <p class="text-xs text-gray-500">Leave blank to use the default copy.</p>
                                            </div>
                                            <div class="rounded-md border border-gray-200 bg-white p-3">
                                                <h5 class="text-xs font-semibold text-gray-700">Placeholders</h5>
                                                <ul class="mt-2 space-y-1 text-xs text-gray-600">
                                                    @foreach($userOnboardingPlaceholders as $placeholder => $description)
                                                        <li>
                                                            <code class="rounded bg-gray-200 px-1 py-0.5 text-[11px]">{{ $placeholder }}</code>
                                                            <span class="ml-1">{{ $description }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        </div>

                                        <div class="rounded-md border border-yellow-100 bg-yellow-50 p-3 text-xs text-yellow-700">
                                            SMS delivery requires Twilio SMS to be active and a phone number on the employee profile. The temporary password is taken from the invite form.
                                        </div>
                                    </div>

                                    <div class="space-y-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                        <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                            <div>
                                                <h4 class="text-sm font-semibold text-gray-800">Requisition SMS alerts</h4>
                                                <p class="text-xs text-gray-600">Configure who receives SMS notifications and tailor the message for each role.</p>
                                            </div>
                                            <details class="w-full rounded-md border border-gray-200 bg-white p-3 text-sm text-gray-600 md:w-auto">
                                                <summary class="cursor-pointer text-sm font-medium text-gray-700">Template placeholders</summary>
                                                <ul class="mt-2 space-y-1 text-xs">
                                                    @foreach($requisitionTemplatePlaceholders as $placeholder => $description)
                                                        <li>
                                                            <code class="rounded bg-gray-200 px-1 py-0.5 text-[11px]">{{ $placeholder }}</code>
                                                            <span class="ml-1 text-gray-600">{{ $description }}</span>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        </div>

                                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                            <div class="space-y-4">
                                                <div>
                                                    <h5 class="text-sm font-semibold text-gray-800">When a requisition is submitted</h5>
                                                    <p class="mt-1 text-xs text-gray-500">Send confirmations to chefs and alert purchasing and management teams instantly.</p>
                                                </div>
                                                @foreach($requisitionNotificationRoles as $roleKey => $roleLabel)
                                                    @php
                                                        $submittedPhoneDefault = implode("\n", $notificationSettings->requisition_submitted_notification_phones[$roleKey] ?? []);
                                                        $submittedTemplateDefault = $defaultRequisitionTemplates['submitted'][$roleKey] ?? '';
                                                        $submittedTemplateValue = old("requisition_submitted_templates.$roleKey", $notificationSettings->requisition_submitted_templates[$roleKey] ?? $submittedTemplateDefault);
                                                    @endphp
                                                    <div class="space-y-3 rounded-md border border-gray-200 bg-white p-4 shadow-sm">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-sm font-medium text-gray-700">{{ $roleLabel }}</span>
                                                            <button type="button" class="text-xs font-medium text-blue-600 hover:underline" x-on:click.prevent="$refs.submittedTemplate{{ Str::studly($roleKey) }}.value = @js($submittedTemplateDefault)">
                                                                Use default message
                                                            </button>
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-gray-600" for="requisition_submitted_phones_{{ $roleKey }}">SMS recipients</label>
                                                            <textarea id="requisition_submitted_phones_{{ $roleKey }}" name="requisition_submitted_phones[{{ $roleKey }}]" rows="2" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="+15555550123&#10;+15555550124">{{ old("requisition_submitted_phones.$roleKey", $submittedPhoneDefault) }}</textarea>
                                                            <p class="mt-1 text-xs text-gray-500">One phone number per line in international format.</p>
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-gray-600" for="requisition_submitted_templates_{{ $roleKey }}">SMS template</label>
                                                            <textarea x-ref="submittedTemplate{{ Str::studly($roleKey) }}" id="requisition_submitted_templates_{{ $roleKey }}" name="requisition_submitted_templates[{{ $roleKey }}]" rows="3" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ $submittedTemplateValue }}</textarea>
                                                            <p class="mt-1 text-xs text-gray-500">Leave blank to fall back to the default message.</p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="space-y-4">
                                                <div>
                                                    <h5 class="text-sm font-semibold text-gray-800">When a requisition is approved</h5>
                                                    <p class="mt-1 text-xs text-gray-500">Notify stakeholders once managers approve a requisition.</p>
                                                </div>
                                                @foreach($requisitionNotificationRoles as $roleKey => $roleLabel)
                                                    @php
                                                        $approvedPhoneDefault = implode("\n", $notificationSettings->requisition_approved_notification_phones[$roleKey] ?? []);
                                                        $approvedTemplateDefault = $defaultRequisitionTemplates['approved'][$roleKey] ?? '';
                                                        $approvedTemplateValue = old("requisition_approved_templates.$roleKey", $notificationSettings->requisition_approved_templates[$roleKey] ?? $approvedTemplateDefault);
                                                    @endphp
                                                    <div class="space-y-3 rounded-md border border-gray-200 bg-white p-4 shadow-sm">
                                                        <div class="flex items-center justify-between">
                                                            <span class="text-sm font-medium text-gray-700">{{ $roleLabel }}</span>
                                                            <button type="button" class="text-xs font-medium text-blue-600 hover:underline" x-on:click.prevent="$refs.approvedTemplate{{ Str::studly($roleKey) }}.value = @js($approvedTemplateDefault)">
                                                                Use default message
                                                            </button>
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-gray-600" for="requisition_approved_phones_{{ $roleKey }}">SMS recipients</label>
                                                            <textarea id="requisition_approved_phones_{{ $roleKey }}" name="requisition_approved_phones[{{ $roleKey }}]" rows="2" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="+15555550123&#10;+15555550124">{{ old("requisition_approved_phones.$roleKey", $approvedPhoneDefault) }}</textarea>
                                                            <p class="mt-1 text-xs text-gray-500">One phone number per line in international format.</p>
                                                        </div>
                                                        <div>
                                                            <label class="mb-1 block text-xs font-medium text-gray-600" for="requisition_approved_templates_{{ $roleKey }}">SMS template</label>
                                                            <textarea x-ref="approvedTemplate{{ Str::studly($roleKey) }}" id="requisition_approved_templates_{{ $roleKey }}" name="requisition_approved_templates[{{ $roleKey }}]" rows="3" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:ring-blue-500">{{ $approvedTemplateValue }}</textarea>
                                                            <p class="mt-1 text-xs text-gray-500">Leave blank to fall back to the default message.</p>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <div class="rounded-md border border-blue-100 bg-blue-50 p-3 text-xs text-blue-700">
                                            Enable both requisition notifications and SMS alerts to activate these messages. Recipients must have Twilio-compatible numbers.
                                        </div>
                                    </div>

                                    <div class="grid gap-6 md:grid-cols-2">
                                        <div>
                                            <label for="purchase_order_notification_emails" class="mb-1 block text-sm font-medium text-gray-700">Purchasing team emails</label>
                                            <textarea id="purchase_order_notification_emails" name="purchase_order_notification_emails" rows="4" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="purchaser@example.com&#10;ops@example.com">{{ old('purchase_order_notification_emails', implode("\n", $notificationSettings->purchase_order_notification_emails ?? [])) }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">One email per line. Recipients are notified when a PO is approved.</p>
                                        </div>
                                        <div>
                                            <label for="purchase_order_notification_phones" class="mb-1 block text-sm font-medium text-gray-700">Purchasing team phones</label>
                                            <textarea id="purchase_order_notification_phones" name="purchase_order_notification_phones" rows="4" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="+15555550123&#10;+15555550456">{{ old('purchase_order_notification_phones', implode("\n", $notificationSettings->purchase_order_notification_phones ?? [])) }}</textarea>
                                            <p class="mt-1 text-xs text-gray-500">One phone number per line in international format. Used for SMS and WhatsApp notifications.</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                        Save changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    x-show="activeTab === 'products'"
                    x-cloak
                    class="space-y-6"
                >
                    <div class="overflow-hidden rounded-2xl border border-gray-200">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-xl font-semibold text-gray-900">Product catalog</h2>
                            <p class="mt-1 text-sm text-gray-500">Manage vendors, items, and categories from a single workspace.</p>
                        </div>

                        <div class="space-y-6 p-6">
                            <div class="overflow-x-auto">
                                <div class="flex gap-3 pb-1">
                                    @foreach ($productSectionsMeta as $sectionKey => $meta)
                                        <button
                                            type="button"
                                            @click="productSection = '{{ $sectionKey }}'"
                                            class="flex flex-col rounded-xl border px-4 py-3 text-left text-sm transition"
                                            :class="productSection === '{{ $sectionKey }}' ? 'border-blue-500 bg-blue-50 text-blue-700 shadow-sm' : 'border-gray-200 bg-white text-gray-600 hover:border-blue-200 hover:text-blue-600'"
                                        >
                                            <span class="font-semibold">{{ $meta['label'] }}</span>
                                            <span class="mt-1 text-xs text-gray-500" :class="productSection === '{{ $sectionKey }}' ? 'text-blue-600' : ''">{{ $meta['summary'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            @include('settings.tabs.vendors')

                            <div
                                x-show="productSection === 'items'"
                                x-cloak
                                class="space-y-6"
                            >
                                <div class="space-y-3 sm:flex sm:items-center sm:justify-between sm:gap-3 sm:space-y-0">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">Items</h3>
                                        <p class="mt-1 text-sm text-gray-600">Maintain the catalog that powers requisitions, purchase orders, and stock alerts.</p>
                                    </div>
                                    @if($isEditingItem)
                                        <a href="{{ route('settings', ['tab' => 'products', 'product_section' => 'items']) }}" class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                                            Exit edit mode
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

                                <div class="overflow-hidden rounded-lg border border-gray-200 shadow-sm">
                                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-200 bg-gray-50 px-6 py-4">
                                        <div class="space-y-1">
                                            <div class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
                                                <span>{{ $items->count() }} items</span>
                                                <span aria-hidden="true">•</span>
                                                <span>{{ $activeVendors->count() }} active vendors</span>
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
                                            <button type="button" @if($activeVendors->isNotEmpty()) @click.prevent="open('showItemModal')" @endif @if($activeVendors->isEmpty()) disabled @endif class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700 {{ $activeVendors->isEmpty() ? 'cursor-not-allowed opacity-60 hover:bg-blue-600' : '' }}">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                                </svg>
                                                Add new item
                                            </button>
                                        </div>
                                        @if($activeVendors->isEmpty())
                                            <p class="w-full text-right text-xs font-medium text-red-600">Add a vendor before creating new items.</p>
                                        @endif
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
                                                                <span class="{{ $isLowStock ? 'font-semibold text-red-600' : '' }}">{{ rtrim(rtrim(number_format((float) $item->stock, 2), '0'), '.') }}</span>
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
                                                                <a href="{{ route('settings', ['tab' => 'products', 'product_section' => 'items', 'edit_item' => $item->id]) }}" class="font-medium text-blue-600 hover:text-blue-700">Edit</a>
                                                                <form action="{{ route('items.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete {{ $item->name }}? This cannot be undone.');">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <input type="hidden" name="return_to" value="settings">
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
                            </div>

                            <div
                                x-show="productSection === 'categories'"
                                x-cloak
                                class="space-y-6"
                            >
                                <div class="space-y-3 sm:flex sm:items-center sm:justify-between sm:gap-3 sm:space-y-0">
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900">Item categories</h3>
                                        <p class="mt-1 text-sm text-gray-600">Organize your catalog into reusable groups to speed up purchasing and reporting.</p>
                                    </div>
                                    <button type="button" @click.prevent="open('showCategoryModal')" class="inline-flex items-center gap-2 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                                        </svg>
                                        Add category
                                    </button>
                                </div>

                                @if ($errors->categories->any())
                                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                        <ul class="list-disc space-y-1 pl-5">
                                            @foreach ($errors->categories->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif

                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-5">
                                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total categories</p>
                                        <p class="mt-2 text-2xl font-semibold text-gray-900">{{ $categoryStats['total'] }}</p>
                                    </div>
                                    <div class="rounded-xl border border-green-200 bg-green-50 px-4 py-5">
                                        <p class="text-xs font-medium uppercase tracking-wide text-green-700">Active</p>
                                        <p class="mt-2 text-2xl font-semibold text-green-800">{{ $categoryStats['active'] }}</p>
                                    </div>
                                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-5">
                                        <p class="text-xs font-medium uppercase tracking-wide text-amber-700">Inactive</p>
                                        <p class="mt-2 text-2xl font-semibold text-amber-800">{{ $categoryStats['inactive'] }}</p>
                                    </div>
                                </div>

                                <div class="overflow-hidden rounded-xl border border-gray-200">
                                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                                        <thead class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                            <tr>
                                                <th class="px-6 py-3 text-left">Category</th>
                                                <th class="px-6 py-3 text-left">Status</th>
                                                <th class="px-6 py-3 text-left">Created</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @forelse ($itemCategories as $category)
                                                <tr>
                                                    <td class="px-6 py-4 text-gray-900">{{ $category->name }}</td>
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex items-center rounded-full px-2 py-1 text-xs font-semibold {{ $category->status === 'active' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-700' }}">
                                                            {{ Str::title($category->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 text-gray-600">{{ optional($category->created_at)->format('M d, Y') ?? '—' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="3" class="px-6 py-8 text-center text-sm text-gray-500">No categories yet. Add one to start grouping items.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700">
                                    Categories become available immediately for new items, requisitions, and purchase orders.
                                </div>
                            </div>
                        </div>
                    </div>

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
                                    <h3 class="text-lg font-semibold text-gray-900">{{ $isEditingItem ? 'Edit item' : 'Add new item' }}</h3>
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
                                    <input type="hidden" name="return_to" value="settings">
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
                                                @if ($selectedUom !== '' && ! $normalizedUnits->contains(Str::lower($selectedUom)))
                                                    <option value="{{ $selectedUom }}" selected>{{ Str::upper($selectedUom) }}</option>
                                                @endif
                                            </select>
                                        </div>
                                        <div>
                                            <label for="item-vendor" class="block text-sm font-medium text-gray-700">Vendor/Supplier <span class="text-red-500">*</span></label>
                                            @php
                                                $vendorOptions = $vendors->sortBy('name')->values();
                                                $currentVendorId = old('vendor_id', $selectedVendorId);
                                            @endphp
                                            <select id="item-vendor" name="vendor_id" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                <option value="" disabled @selected(empty($currentVendorId))>Select vendor</option>
                                                @foreach ($vendorOptions as $vendorOption)
                                                    <option value="{{ $vendorOption->id }}" @selected((string) $currentVendorId === (string) $vendorOption->id)">
                                                        {{ $vendorOption->name }}{{ !$vendorOption->is_active ? ' (inactive)' : '' }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <p class="mt-1 text-xs text-gray-500">Need a new supplier? Switch to the Vendors tab to create it first.</p>
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
                                            {{ $isEditingItem ? 'Save item' : 'Create item' }}
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

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
                                    <h3 class="text-lg font-semibold text-gray-900">Create category</h3>
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
                                        <label for="category-name" class="block text-sm font-medium text-gray-700">Category name <span class="text-red-500">*</span></label>
                                        <input id="category-name" name="category_name" type="text" value="{{ old('category_name') }}" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    </div>
                                    <div>
                                        <label for="category-status" class="block text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                        @php $categoryStatus = old('category_status', 'active'); @endphp
                                        <select id="category-status" name="category_status" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            <option value="active" @selected($categoryStatus === 'active')>Active</option>
                                            <option value="inactive" @selected($categoryStatus === 'inactive')>Inactive</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center justify-end gap-3">
                                        <button type="button" class="text-sm font-medium text-gray-500 hover:text-gray-700" @click="close('showCategoryModal')">Cancel</button>
                                        <button type="submit" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                            Save category
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    x-show="activeTab === 'integration'"
                    x-cloak
                    class="space-y-6"
                >
                    <div class="overflow-hidden rounded-2xl border border-gray-200">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('settings.sections.integration') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">Connect your POS, email provider, and messaging integrations.</p>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="active_tab" value="integration">

                                @php
                                    $loyverseConnected = filled($integrationSettings->loyverse_api_key);
                                    $smtpConfigured = filled($integrationSettings->smtp_host) && filled($integrationSettings->smtp_username);
                                    $twilioConfigured = filled($integrationSettings->twilio_account_sid) && filled($integrationSettings->twilio_auth_token);
                                    $loyverseRedirectUrl = route('api.loyverse.webhook');
                                @endphp

                                <div class="space-y-6">
                                    <div class="rounded-lg border border-gray-200 p-4">
                                        <div class="mb-4 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="rounded-lg bg-blue-100 p-2">
                                                    <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900">Loyverse POS</h4>
                                                    <p class="text-sm text-gray-500">Sync sales data from Loyverse.</p>
                                                </div>
                                            </div>
                                            <span class="rounded-full px-2 py-1 text-xs font-medium {{ $loyverseConnected ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $loyverseConnected ? 'Connected' : 'Not configured' }}
                                            </span>
                                        </div>
                                        <div class="space-y-3">
                                            <div>
                                                <label for="loyverse_api_key" class="mb-1 block text-sm font-medium text-gray-700">API key</label>
                                                <input type="password" name="loyverse_api_key" id="loyverse_api_key" value="{{ old('loyverse_api_key', $integrationSettings->loyverse_api_key) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label for="loyverse_redirect_url" class="mb-1 block text-sm font-medium text-gray-700">Redirect URL</label>
                                                <div class="relative">
                                                    <input type="text" id="loyverse_redirect_url" value="{{ $loyverseRedirectUrl }}" readonly class="block w-full cursor-text rounded-md border border-dashed border-blue-300 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-700 focus:outline-none">
                                                </div>
                                                <p class="mt-1 text-xs text-gray-500">Configure this URL in the Loyverse developer portal so webhook events reach your RMS account.</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <input type="checkbox" name="loyverse_auto_sync" id="loyverse_auto_sync" @checked(old('loyverse_auto_sync', $integrationSettings->loyverse_auto_sync)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                <label for="loyverse_auto_sync" class="text-sm text-gray-700">Enable automatic daily sync at 2:00 AM</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-gray-200 p-4">
                                        <div class="mb-4 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="rounded-lg bg-purple-100 p-2">
                                                    <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900">Email service</h4>
                                                    <p class="text-sm text-gray-500">Configure SMTP credentials for outbound emails.</p>
                                                </div>
                                            </div>
                                            <span class="rounded-full px-2 py-1 text-xs font-medium {{ $smtpConfigured ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $smtpConfigured ? 'Connected' : 'Not configured' }}
                                            </span>
                                        </div>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700" for="smtp_host">SMTP host</label>
                                                <input type="text" name="smtp_host" id="smtp_host" value="{{ old('smtp_host', $integrationSettings->smtp_host) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700" for="smtp_port">SMTP port</label>
                                                <input type="text" name="smtp_port" id="smtp_port" value="{{ old('smtp_port', $integrationSettings->smtp_port) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700" for="smtp_username">Username</label>
                                                <input type="text" name="smtp_username" id="smtp_username" value="{{ old('smtp_username', $integrationSettings->smtp_username) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700" for="smtp_password">Password</label>
                                                <input type="password" name="smtp_password" id="smtp_password" value="{{ old('smtp_password', $integrationSettings->smtp_password) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700" for="smtp_encryption">Encryption</label>
                                                <input type="text" name="smtp_encryption" id="smtp_encryption" value="{{ old('smtp_encryption', $integrationSettings->smtp_encryption) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-gray-200 p-4">
                                        <div class="mb-4 flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <div class="rounded-lg bg-emerald-100 p-2">
                                                    <svg class="h-6 w-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2m-4 0h-2m-4 0H5a2 2 0 01-2-2v-1m0-4V6a2 2 0 012-2h14a2 2 0 012 2v5" />
                                                    </svg>
                                                </div>
                                                <div>
                                                    <h4 class="text-sm font-medium text-gray-900">Twilio messaging</h4>
                                                    <p class="text-sm text-gray-500">Drive SMS and WhatsApp alerts for notifications.</p>
                                                </div>
                                            </div>
                                            <span class="rounded-full px-2 py-1 text-xs font-medium {{ $twilioConfigured ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                                {{ $twilioConfigured ? 'Connected' : 'Not configured' }}
                                            </span>
                                        </div>
                                        <div class="grid gap-3 sm:grid-cols-2">
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700" for="twilio_account_sid">Account SID</label>
                                                <input type="text" name="twilio_account_sid" id="twilio_account_sid" value="{{ old('twilio_account_sid', $integrationSettings->twilio_account_sid) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700" for="twilio_auth_token">Auth token</label>
                                                <input type="password" name="twilio_auth_token" id="twilio_auth_token" value="{{ old('twilio_auth_token', $integrationSettings->twilio_auth_token) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700" for="twilio_sms_number">SMS number</label>
                                                <input type="text" name="twilio_sms_number" id="twilio_sms_number" value="{{ old('twilio_sms_number', $integrationSettings->twilio_sms_number) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-sm font-medium text-gray-700" for="twilio_whatsapp_number">WhatsApp number</label>
                                                <input type="text" name="twilio_whatsapp_number" id="twilio_whatsapp_number" value="{{ old('twilio_whatsapp_number', $integrationSettings->twilio_whatsapp_number) }}" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                        </div>
                                        <div class="mt-3 space-y-2">
                                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="twilio_sms_enabled" @checked(old('twilio_sms_enabled', $integrationSettings->twilio_sms_enabled)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                Enable SMS delivery
                                            </label>
                                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="twilio_whatsapp_enabled" @checked(old('twilio_whatsapp_enabled', $integrationSettings->twilio_whatsapp_enabled)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                Enable WhatsApp delivery
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3">
                                    <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                        Save changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    x-show="activeTab === 'security'"
                    x-cloak
                    class="space-y-6"
                >
                    <div class="overflow-hidden rounded-2xl border border-gray-200">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-xl font-semibold text-gray-900">{{ __('settings.sections.security') }}</h2>
                            <p class="mt-1 text-sm text-gray-500">Strengthen access controls and monitor critical activity.</p>
                        </div>
                        <div class="p-6">
                            <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="active_tab" value="security">

                                <div class="space-y-6">
                                    <div class="rounded-lg border border-gray-200 p-4">
                                        <h4 class="mb-4 text-sm font-medium text-gray-900">Change password</h4>
                                        <div class="grid gap-4 sm:grid-cols-2">
                                            <div>
                                                <label for="current_password" class="mb-1 block text-sm font-medium text-gray-700">Current password</label>
                                                <input type="password" name="current_password" id="current_password" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label for="new_password" class="mb-1 block text-sm font-medium text-gray-700">New password</label>
                                                <input type="password" name="new_password" id="new_password" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                            <div>
                                                <label for="confirm_password" class="mb-1 block text-sm font-medium text-gray-700">Confirm new password</label>
                                                <input type="password" name="confirm_password" id="confirm_password" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-4 rounded-lg border border-gray-200 p-4">
                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="two_factor" name="two_factor" type="checkbox" @checked(old('two_factor', $securitySettings->two_factor_enabled)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="two_factor" class="font-medium text-gray-700">Enable two-factor authentication</label>
                                                <p class="text-gray-500">Add an extra layer of security to your account.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="session_timeout" name="session_timeout" type="checkbox" @checked(old('session_timeout', $securitySettings->session_timeout_enabled)) class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="session_timeout" class="font-medium text-gray-700">Automatic session timeout</label>
                                                <p class="text-gray-500">Log out automatically after 30 minutes of inactivity.</p>
                                            </div>
                                        </div>

                                        <div class="flex items-start">
                                            <div class="flex h-5 items-center">
                                                <input id="login_alerts" name="login_alerts" type="checkbox" checked class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                            </div>
                                            <div class="ml-3 text-sm">
                                                <label for="login_alerts" class="font-medium text-gray-700">Login alerts</label>
                                                <p class="text-gray-500">Receive email notifications for new login attempts.</p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-lg border border-gray-200 p-4">
                                        <div class="mb-4 flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-gray-900">Recent activity</h4>
                                            <a href="#" class="text-sm text-blue-600 hover:text-blue-800">View all</a>
                                        </div>
                                        <div class="space-y-2 text-sm text-gray-600">
                                            <div class="flex items-center justify-between">
                                                <span>Last login</span>
                                                <span class="text-gray-900">Today at 2:45 PM</span>
                                            </div>
                                            <div class="flex items-center justify-between">
                                                <span>Password changed</span>
                                                <span class="text-gray-900">15 days ago</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-end gap-3">
                                    <button type="submit" class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                        Save changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div
                    x-show="activeTab === 'users'"
                    x-cloak
                    class="space-y-6"
                >
                    <div class="overflow-hidden rounded-2xl border border-gray-200">
                        <div class="border-b border-gray-200 px-6 py-5">
                            <h2 class="text-xl font-semibold text-gray-900">User management</h2>
                            <p class="mt-1 text-sm text-gray-500">Invite teammates, assign roles, and control their access.</p>
                        </div>
                        <div class="p-6">
                            <div
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
                                        this.editingUser = { ...user };
                                        this.showAddModal = true;
                                    }
                                }"
                                class="space-y-6"
                            >
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-medium text-gray-900">Team members</h3>
                                    <button @click="addUser()" type="button" class="inline-flex items-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-blue-700">
                                        <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add new user
                                    </button>
                                </div>

                                <div class="overflow-hidden rounded-lg border border-gray-200">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">User</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Role</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last login</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-200 bg-white">
                                            <template x-for="user in users" :key="user.id">
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4">
                                                        <div class="flex items-center">
                                                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100">
                                                                <span class="text-sm font-medium text-blue-600" x-text="user.name.charAt(0)"></span>
                                                            </div>
                                                            <div class="ml-4">
                                                                <div class="text-sm font-medium text-gray-900" x-text="user.name"></div>
                                                                <div class="text-sm text-gray-500" x-text="user.email"></div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize" :class="{
                                                            'bg-purple-100 text-purple-800': user.role === 'admin',
                                                            'bg-blue-100 text-blue-800': user.role === 'manager',
                                                            'bg-green-100 text-green-800': user.role === 'chef',
                                                            'bg-yellow-100 text-yellow-800': user.role === 'purchaser'
                                                        }" x-text="user.role"></span>
                                                    </td>
                                                    <td class="px-6 py-4">
                                                        <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold capitalize" :class="user.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" x-text="user.status"></span>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500" x-text="user.last_login"></td>
                                                    <td class="px-6 py-4 text-right">
                                                        <button @click="editUser(user)" type="button" class="mr-4 text-sm font-medium text-blue-600 hover:text-blue-900">Edit</button>
                                                        <button type="button" class="text-sm font-medium text-red-600 hover:text-red-900">Delete</button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>

                                <div
                                    x-cloak
                                    x-show="showAddModal"
                                    class="fixed inset-0 z-40 overflow-y-auto"
                                    aria-labelledby="modal-title"
                                    role="dialog"
                                    aria-modal="true"
                                >
                                    <div class="flex min-h-screen items-end justify-center px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                                        <div x-show="showAddModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showAddModal = false"></div>
                                        <span class="hidden sm:inline-block sm:h-screen sm:align-middle">&#8203;</span>
                                        <div x-show="showAddModal" class="inline-block w-full max-w-2xl transform overflow-hidden rounded-lg bg-white text-left align-bottom shadow-xl transition-all sm:my-8 sm:align-middle">
                                            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                                                <div class="sm:flex sm:items-start">
                                                    <div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
                                                        <h3 class="mb-4 text-lg font-medium leading-6 text-gray-900" id="modal-title">
                                                            <span x-text="editingUser ? 'Edit user' : 'Add new user'"></span>
                                                        </h3>
                                                        <div class="space-y-4">
                                                            <div>
                                                                <label class="mb-1 block text-sm font-medium text-gray-700">Full name <span class="text-red-500">*</span></label>
                                                                <input type="text" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" :value="editingUser ? editingUser.name : ''" placeholder="John Doe">
                                                            </div>
                                                            <div>
                                                                <label class="mb-1 block text-sm font-medium text-gray-700">Email address <span class="text-red-500">*</span></label>
                                                                <input type="email" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" :value="editingUser ? editingUser.email : ''" placeholder="john@example.com">
                                                            </div>
                                                            <div>
                                                                <label class="mb-1 block text-sm font-medium text-gray-700">Role <span class="text-red-500">*</span></label>
                                                                <select class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                                    <option value="">Select role</option>
                                                                    <option value="admin">Admin</option>
                                                                    <option value="manager">Manager</option>
                                                                    <option value="chef">Chef</option>
                                                                    <option value="purchaser">Purchaser</option>
                                                                    <option value="accountant">Accountant</option>
                                                                    <option value="viewer">Viewer</option>
                                                                </select>
                                                            </div>
                                                            <div x-show="!editingUser">
                                                                <label class="mb-1 block text-sm font-medium text-gray-700">Password <span class="text-red-500">*</span></label>
                                                                <input type="password" class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" placeholder="••••••••">
                                                            </div>
                                                            <div>
                                                                <label class="mb-2 block text-sm font-medium text-gray-700">Permissions</label>
                                                                <div class="grid max-h-48 grid-cols-2 gap-3 overflow-y-auto rounded-md border border-gray-200 p-3 text-sm">
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        View requisitions
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        Create requisitions
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        Approve requisitions
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        Reject requisitions
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        View purchase orders
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        Create purchase orders
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        View expenses
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        Create expenses
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        View reports
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        Manage payroll
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        View users
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        Manage users
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        View settings
                                                                    </label>
                                                                    <label class="flex items-center gap-2">
                                                                        <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                        Manage settings
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <label class="mb-1 block text-sm font-medium text-gray-700">Status</label>
                                                                <select class="block w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                                    <option value="active">Active</option>
                                                                    <option value="inactive">Inactive</option>
                                                                </select>
                                                            </div>
                                                            <div x-show="!editingUser">
                                                                <label class="flex items-center gap-2 text-sm text-gray-700">
                                                                    <input type="checkbox" checked class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                                                    Send welcome email with login credentials
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                                <button type="button" @click="showAddModal = false" class="inline-flex w-full justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-base font-medium text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:ml-3 sm:w-auto sm:text-sm">
                                                    <span x-text="editingUser ? 'Update user' : 'Create user'"></span>
                                                </button>
                                                <button type="button" @click="showAddModal = false" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 sm:mt-0 sm:w-auto sm:text-sm">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-600">
                                    <h4 class="mb-3 text-sm font-medium text-gray-900">Role descriptions</h4>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div><span class="font-medium text-purple-700">Admin:</span> Full system access and user management.</div>
                                        <div><span class="font-medium text-blue-700">Manager:</span> Approve requisitions and view reports.</div>
                                        <div><span class="font-medium text-green-700">Chef:</span> Create requisitions and monitor inventory.</div>
                                        <div><span class="font-medium text-yellow-700">Purchaser:</span> Create purchase orders and manage suppliers.</div>
                                        <div><span class="font-medium text-indigo-700">Accountant:</span> Manage expenses and financial reports.</div>
                                        <div><span class="font-medium text-gray-700">Viewer:</span> Read-only access to all modules.</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    <strong class="font-medium">Note:</strong> Changes to settings take effect immediately. Some integrations may require reconnection after updating credentials.
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
