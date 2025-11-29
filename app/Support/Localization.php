<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Arr;

class Localization
{
    /**
     * Retrieve all supported locales keyed by code.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        return config('app.supported_locales', []);
    }

    /**
     * Get metadata for the provided locale code.
     *
     * @param  string|null  $locale
     * @return array<string, mixed>
     */
    public static function get(?string $locale = null): array
    {
        $resolved = $locale ?: static::default();
        $locales = static::all();

        if (array_key_exists($resolved, $locales)) {
            return static::mergeWithDefaults($resolved, $locales[$resolved]);
        }

        return static::mergeWithDefaults(static::default(), $locales[static::default()] ?? []);
    }

    /**
     * Determine if a locale code is supported.
     */
    public static function isSupported(?string $locale): bool
    {
        return $locale !== null && array_key_exists($locale, static::all());
    }

    /**
     * Retrieve the label for the given locale.
     */
    public static function label(?string $locale = null): string
    {
        $data = static::get($locale);

        return (string) ($data['label'] ?? strtoupper((string) $data['code']));
    }

    /**
     * Retrieve the native label for the given locale.
     */
    public static function nativeLabel(?string $locale = null): string
    {
        $data = static::get($locale);

        return (string) ($data['native'] ?? $data['label'] ?? strtoupper((string) $data['code']));
    }

    /**
     * Retrieve the configured locales codes.
     *
     * @return array<int, string>
     */
    public static function codes(): array
    {
        return array_keys(static::all());
    }

    /**
     * Resolve the default locale for the application.
     */
    public static function default(): string
    {
        return (string) config('app.locale', 'en');
    }

    /**
     * Merge provided locale data with sensible defaults.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected static function mergeWithDefaults(string $code, array $data): array
    {
        return array_merge([
            'code' => $code,
            'label' => strtoupper($code),
            'native' => strtoupper($code),
            'regional' => null,
        ], Arr::wrap($data));
    }
}
