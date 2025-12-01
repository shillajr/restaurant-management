<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $activeLocale['code'] ?? app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
        $generatedTitle = $routeName
            ? (string) \Illuminate\Support\Str::of($routeName)
                ->replace(['.', '-', '_'], ' ')
                ->title()
            : 'Dashboard';
    @endphp
    <title>@yield('title', $generatedTitle) - RMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none !important;}</style>
    @stack('styles')
</head>
<body class="bg-gray-50">
    @include('partials.navigation')
    @php
        $appLocalePayload = [
            'code' => $activeLocale['code'] ?? app()->getLocale(),
            'label' => $activeLocale['label'] ?? locale_label(),
            'native' => $activeLocale['native'] ?? locale_native_label(),
            'supported' => $supportedLocales ?? supported_locales(),
        ];
    @endphp
    <script>
        window.appLocale = @json($appLocalePayload);

        @if(isset($activeCurrency))
        window.appCurrency = @json($activeCurrency);
        @endif
    </script>
    @stack('scripts')
</body>
</html>
