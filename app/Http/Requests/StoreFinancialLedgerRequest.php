<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFinancialLedgerRequest extends FormRequest
{
    public const ENTRY_TYPE_CUSTOMER_RECEIVABLE = 'customer_receivable';
    public const ENTRY_TYPE_VENDOR_DEBT = 'vendor_debt';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $entryType = $this->input('entry_type');

        $baseRules = [
            'entry_type' => ['required', Rule::in([self::ENTRY_TYPE_CUSTOMER_RECEIVABLE, self::ENTRY_TYPE_VENDOR_DEBT])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        return match ($entryType) {
            self::ENTRY_TYPE_CUSTOMER_RECEIVABLE => array_merge($baseRules, [
                'customer_first_name' => ['required', 'string', 'max:255'],
                'customer_last_name' => ['required', 'string', 'max:255'],
                'customer_phone' => ['required', 'string', 'max:50'],
                'amount' => ['required', 'numeric', 'min:0.01'],
            ]),
            self::ENTRY_TYPE_VENDOR_DEBT => array_merge($baseRules, [
                'purchase_order_id' => ['required', 'exists:purchase_orders,id'],
                'po_item_keys' => ['required', 'array', 'min:1'],
                'po_item_keys.*' => ['string', 'regex:/^\d+$/'],
            ]),
            default => $baseRules,
        };
    }

    public function messages(): array
    {
        return [
            'po_item_keys.*.regex' => 'Item selections must be provided as numeric identifiers.',
        ];
    }
}
