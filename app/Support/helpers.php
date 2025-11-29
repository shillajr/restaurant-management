<?php

declare(strict_types=1);

use App\Support\Currency;
use App\Support\Localization;

if (! function_exists('active_currency')) {
    /**
     * Retrieve the active currency data array shared for the current request.
     *
     * @return array<string, mixed>
     */
    function active_currency(): array
    {
        /** @var array<string, mixed>|null $currency */
        $currency = app()->bound('activeCurrency') ? app('activeCurrency') : null;

        if (is_array($currency)) {
            return $currency;
        }

        return Currency::get();
    }
}

if (! function_exists('currency_code')) {
    function currency_code(?string $code = null): string
    {
        return Currency::get($code)['code'] ?? Currency::defaultCode();
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol(?string $code = null): string
    {
        return Currency::symbol($code);
    }
}

if (! function_exists('currency_label')) {
    function currency_label(?string $code = null): string
    {
        return Currency::label($code);
    }
}

if (! function_exists('currency_format')) {
    function currency_format(float|int|string|null $amount, ?string $code = null): string
    {
        return Currency::format($amount, $code);
    }
}

if (! function_exists('currency_options')) {
    /**
     * Retrieve an array of [code => label] for dropdowns.
     *
     * @return array<string, string>
     */
    function currency_options(): array
    {
        return collect(Currency::all())
            ->map(fn (array $data) => Currency::label($data['code'] ?? null))
            ->toArray();
    }
}

if (! function_exists('supported_locales')) {
    /**
     * Retrieve an array of supported locales keyed by code.
     *
     * @return array<string, array<string, mixed>>
     */
    function supported_locales(): array
    {
        return Localization::all();
    }
}

if (! function_exists('locale_label')) {
    function locale_label(?string $locale = null): string
    {
        return Localization::label($locale);
    }
}

if (! function_exists('locale_native_label')) {
    function locale_native_label(?string $locale = null): string
    {
        return Localization::nativeLabel($locale);
    }
}
