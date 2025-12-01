@php
    $sectionPageTitle = trim((string) $__env->yieldContent('page-title'));
    $layoutTitle = trim((string) $__env->yieldContent('title'));
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
    $routeDerivedTitle = $routeName
        ? (string) \Illuminate\Support\Str::of($routeName)
            ->replace(['.', '-', '_'], ' ')
            ->title()
        : 'Dashboard';
    $resolvedTitle = $sectionPageTitle !== ''
        ? $sectionPageTitle
        : ($layoutTitle !== '' ? $layoutTitle : $routeDerivedTitle);
@endphp

<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">{{ $resolvedTitle }}</h1>
                @if(isset($pageDescription))
                    <p class="mt-1 text-sm text-gray-600">{{ $pageDescription }}</p>
                @endif
            </div>
            <div class="flex items-center gap-3">
                @yield('page-actions')
            </div>
        </div>
    </div>
</header>
