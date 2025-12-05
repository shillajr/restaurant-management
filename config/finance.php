<?php

return [
    'currency_code' => env('FINANCE_CURRENCY_CODE', 'TZS'),
    'currency_symbol' => env('FINANCE_CURRENCY_SYMBOL', 'TZS'),
    'reminder_cadence_days' => (int) env('FINANCE_REMINDER_CADENCE_DAYS', 7),
    'vendor_debt_max_items' => (int) env('FINANCE_VENDOR_DEBT_MAX_ITEMS', 20),
    'report_default_range' => env('FINANCE_REPORT_DEFAULT_RANGE', 'today'),
    'payment_methods' => collect(explode(',', (string) env('FINANCE_PAYMENT_METHODS', 'cash,card,mobile_money,bank_transfer')))
        ->map(fn ($value) => trim((string) $value))
        ->filter()
        ->mapWithKeys(function (string $method) {
            $label = match ($method) {
                'cash' => 'Cash',
                'card' => 'Card',
                'mobile_money' => 'Mobile Money',
                'bank_transfer' => 'Bank Transfer',
                default => ucwords(str_replace('_', ' ', $method)),
            };

            return [$method => $label];
        })
        ->all(),
];
