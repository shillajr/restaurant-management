<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">@yield('page-title', 'Page Title')</h1>
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
