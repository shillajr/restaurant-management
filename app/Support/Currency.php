<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Arr;

class Currency
{
    /**
     * Get the default currency code.
     */
    public static function defaultCode(): string
    {
        return config('currencies.default', 'USD');
    }

    /**
     * Get all supported currencies keyed by code.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return config('currencies.supported', []);
    }

    /**
     * Get all supported currency codes.
     *
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    /**
     * Resolve currency configuration for the provided code.
     * Falls back to the default currency when the code is unsupported.
     *
     * @return array<string, mixed>
     */
    public static function get(?string $code = null): array
    {
        $supported = self::all();
        $lookupCode = strtoupper($code ?? self::resolveActiveCode());

        $currency = Arr::get($supported, $lookupCode);

        if (! $currency) {
            $currency = Arr::get($supported, self::defaultCode(), [
                'code' => self::defaultCode(),
                'symbol' => '$',
                'name' => 'US Dollar',
                'precision' => 2,
            ]);
        }

        // Ensure key values always exist
        $currency['code'] ??= $lookupCode;
        $currency['symbol'] ??= '';
        $currency['name'] ??= $currency['code'];
        $currency['precision'] ??= 2;

        return $currency;
    }

    /**
     * Determine the currency code that should be used when none is provided.
     */
    protected static function resolveActiveCode(): string
    {
        if (app()->bound('activeCurrency')) {
            $active = app('activeCurrency');

            if (is_array($active) && ! empty($active['code'])) {
                return (string) strtoupper((string) $active['code']);
            }
        }

        return self::defaultCode();
    }

    /**
     * Get the currency symbol for a code.
     */
    public static function symbol(?string $code = null): string
    {
        return (string) Arr::get(self::get($code), 'symbol', '');
    }

    /**
     * Get a human readable label that always returns the ISO currency code.
     */
    public static function label(?string $code = null): string
    {
        $currency = self::get($code);
        return $currency['code'];
    }

    /**
     * Format a numeric amount in the provided currency.
     */
    public static function format(float|int|string|null $amount, ?string $code = null): string
    {
        $currency = self::get($code);
        $numericAmount = is_null($amount) ? 0.0 : (float) $amount;
        $precision = (int) $currency['precision'];

        $prefix = $currency['code'];
        $formatted = number_format($numericAmount, $precision, '.', ',');

        return trim(sprintf('%s %s', $prefix, $formatted));
    }

    /**
     * Format a numeric amount without the symbol, only the code.
     */
    public static function formatWithoutSymbol(float|int|string|null $amount, ?string $code = null): string
    {
        $currency = self::get($code);
        $numericAmount = is_null($amount) ? 0.0 : (float) $amount;
        $precision = (int) $currency['precision'];
        $formatted = number_format($numericAmount, $precision, '.', ',');

        return sprintf('%s %s', $currency['code'], $formatted);
    }
}
