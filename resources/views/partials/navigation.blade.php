@php
    $navGroups = [
        'main' => [
            [
                'label' => __('navigation.links.dashboard'),
                'route' => 'dashboard',
                'active' => ['dashboard'],
                'permission' => null,
            ],
            [
                'label' => __('navigation.links.requisitions'),
                'route' => 'chef-requisitions.index',
                'active' => ['chef-requisitions.*'],
                'permission' => 'create requisitions',
            ],
        ],
        'finance' => [
            [
                'label' => __('navigation.links.purchase_orders'),
                'route' => 'purchase-orders.index',
                'active' => ['purchase-orders.*'],
                'permission' => 'create purchase orders',
            ],
            [
                'label' => __('navigation.links.expenses'),
                'route' => 'expenses.index',
                'active' => ['expenses.*'],
                'permission' => 'create expenses',
            ],
            [
                'label' => __('navigation.links.financial_ledgers'),
                'route' => 'financial-ledgers.index',
                'active' => ['financial-ledgers.*'],
                'permission' => ['approve purchase orders', 'create purchase orders', 'view financial ledgers'],
            ],
        ],
        'insights' => [
            [
                'label' => __('navigation.links.reports'),
                'route' => 'reports.index',
                'active' => ['reports.*'],
                'permission' => 'view reports',
            ],
        ],
        'system' => [
            [
                'label' => __('navigation.links.settings'),
                'route' => 'settings',
                'active' => ['settings'],
                'permission' => null,
            ],
        ],
    ];

    $peopleRoutes = ['payroll.*', 'loans.*', 'employees.*'];
    $peopleSectionActive = request()->routeIs($peopleRoutes);
    $user = auth()->user();

    $canAccessNavItem = function (array $item) use ($user): bool {
        $required = $item['permission'] ?? null;

        if (! $required) {
            return true;
        }

        if (! $user) {
            return false;
        }

        $permissions = is_array($required) ? $required : [$required];

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return true;
            }
        }

        return false;
    };

    $availableGroups = collect($navGroups)->map(function ($items) use ($canAccessNavItem) {
        return collect($items)->filter(fn ($item) => $canAccessNavItem($item))->values();
    });
@endphp

<div class="flex h-screen bg-gray-50" x-data="{ sidebarOpen: false, payrollOpen: {{ $peopleSectionActive ? 'true' : 'false' }} }">
    <!-- Sidebar -->
    <aside class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 transform transition-transform duration-300 ease-in-out lg:translate-x-0 lg:static lg:inset-0"
           :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'">
        <div class="flex h-full flex-col">

            <!-- Branding -->
            <div class="flex items-center gap-3 p-6">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 text-lg font-semibold text-white">
                    R
                </div>
                <div>
                    <p class="text-base font-semibold text-gray-900">{{ $appBrandName ?? __('common.app.name_short') }}</p>
                    <p class="text-xs text-gray-500">{{ __('common.app.tagline') }}</p>
                </div>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-4 pb-6 pt-2">
                <div class="space-y-6">
                    @php
                        $baseClasses = 'flex items-center px-3 py-2 text-sm font-medium text-gray-800 rounded-lg transition-colors duration-150 ease-in-out relative hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-200 active:bg-indigo-50 active:text-indigo-600';
                        $subBase = 'block px-3 py-2 text-sm text-gray-500 rounded-lg transition-colors duration-150 hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-200 active:bg-indigo-50 active:text-indigo-600';
                    @endphp
                    <!-- Main group -->
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase text-gray-400">{{ __('navigation.groups.main') }}</p>
                        <ul class="space-y-1">
                            @foreach ($availableGroups['main'] as $item)
                                @php
                                    $isActive = request()->routeIs($item['active']);
                                    $linkClasses = $isActive ? $baseClasses . ' bg-indigo-50 text-indigo-600 font-semibold' : $baseClasses;
                                @endphp
                                <li>
                                    <a href="{{ route($item['route']) }}" class="{{ $linkClasses }}">
                                        <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <!-- Finance group -->
                    @if($availableGroups['finance']->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase text-gray-400">{{ __('navigation.groups.finance') }}</p>
                            <ul class="space-y-1">
                                @foreach ($availableGroups['finance'] as $item)
                                    @php
                                        $isActive = request()->routeIs($item['active']);
                                        $linkClasses = $isActive ? $baseClasses . ' bg-indigo-50 text-indigo-600 font-semibold' : $baseClasses;
                                    @endphp
                                    <li>
                                        <a href="{{ route($item['route']) }}" class="{{ $linkClasses }}">
                                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(auth()->user()->hasAnyRole(['admin', 'manager']))
                    <!-- People group -->
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase text-gray-400">{{ __('navigation.groups.people') }}</p>
                        <div>
                            @php
                                $baseButtonClasses = 'flex items-center w-full px-3 py-2 text-sm font-medium text-gray-800 text-left rounded-lg transition-colors duration-150 ease-in-out hover:bg-gray-100 hover:text-gray-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-indigo-200 active:bg-indigo-50 active:text-indigo-600';
                                $buttonClasses = $peopleSectionActive ? $baseButtonClasses . ' bg-indigo-50 text-indigo-600 font-semibold' : $baseButtonClasses;
                            @endphp
                            <button type="button"
                                    @click="payrollOpen = !payrollOpen"
                                    class="{{ $buttonClasses }}">
                                <span class="flex-1 truncate">{{ __('navigation.hr.toggle') }}</span>
                                <span class="ml-auto flex items-center text-gray-400 transition-transform duration-200" :class="payrollOpen ? 'rotate-180' : ''">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                            </button>

                            <ul x-show="payrollOpen"
                                x-collapse
                                class="mt-2 space-y-1 pl-6">
                                <li>
                                    @php $isPayroll = request()->routeIs('payroll.*'); @endphp
                                    @php
                                        $subClass = $isPayroll ? $subBase . ' bg-indigo-50 text-indigo-600 font-medium' : $subBase;
                                    @endphp
                                    <a href="{{ route('payroll.index') }}" class="{{ $subClass }}">{{ __('navigation.hr.payroll') }}</a>
                                </li>
                                <li>
                                    @php $isLoans = request()->routeIs('loans.*'); @endphp
                                    @php
                                        $subClass = $isLoans ? $subBase . ' bg-indigo-50 text-indigo-600 font-medium' : $subBase;
                                    @endphp
                                    <a href="{{ route('loans.index') }}" class="{{ $subClass }}">{{ __('navigation.hr.loans') }}</a>
                                </li>
                                <li>
                                    @php $isSalary = request()->routeIs('employees.*'); @endphp
                                    @php
                                        $subClass = $isSalary ? $subBase . ' bg-indigo-50 text-indigo-600 font-medium' : $subBase;
                                    @endphp
                                    <a href="{{ route('employees.salary.index') }}" class="{{ $subClass }}">{{ __('navigation.hr.salary_management') }}</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    @endif

                    <!-- Insights group -->
                    @if($availableGroups['insights']->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase text-gray-400">{{ __('navigation.groups.insights') }}</p>
                            <ul class="space-y-1">
                                @foreach ($availableGroups['insights'] as $item)
                                    @php
                                        $isActive = request()->routeIs($item['active']);
                                        $linkClasses = $isActive ? $baseClasses . ' bg-indigo-50 text-indigo-600 font-semibold' : $baseClasses;
                                    @endphp
                                    <li>
                                        <a href="{{ route($item['route']) }}" class="{{ $linkClasses }}">
                                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- System group -->
                    @if($availableGroups['system']->isNotEmpty())
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase text-gray-400">{{ __('navigation.groups.system') }}</p>
                            <ul class="space-y-1">
                                @foreach ($availableGroups['system'] as $item)
                                    @php
                                        $isActive = request()->routeIs($item['active']);
                                        $linkClasses = $isActive ? $baseClasses . ' bg-indigo-50 text-indigo-600 font-semibold' : $baseClasses;
                                    @endphp
                                    <li>
                                        <a href="{{ route($item['route']) }}" class="{{ $linkClasses }}">
                                            <span class="flex-1 truncate">{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </div>
            </nav>

            <!-- User Section -->
            <div class="border-t border-gray-200 p-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 text-sm font-semibold text-white">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div class="min-w-0 text-sm">
                        <p class="truncate font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-red-600 hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-200">
                        {{ __('navigation.user.logout') }}
                    </button>
                </form>
            </div>
        </div>
    </aside>

    <!-- Mobile Overlay -->
    <div x-show="sidebarOpen" 
         @click="sidebarOpen = false"
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
         style="display: none;"></div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Mobile Header -->
        <header class="lg:hidden bg-white border-b border-gray-200 px-4 py-3 flex items-center justify-between">
            <button @click="sidebarOpen = true" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                    <span class="text-white font-bold text-sm">R</span>
                </div>
                <span class="text-lg font-semibold text-gray-900">{{ $appBrandName ?? __('common.app.name_short') }}</span>
            </div>
            <div class="w-6"></div>
        </header>

        <!-- Page Content -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50">
            @yield('content')
        </main>
    </div>
</div>
