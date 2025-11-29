<?php

return [
    'create' => [
        'title' => 'Add Expense',
        'description' => 'Record expense transactions with multiple items',
        'back_to_dashboard' => 'Back to Dashboard',
        'expense_date' => 'Expense Date',
        'items' => [
            'heading' => 'Expense Items',
            'add_button' => 'Add Item',
            'labels' => [
                'item' => 'Item',
                'vendor' => 'Vendor',
                'quantity' => 'Quantity',
                'unit_price' => 'Unit Price',
                'payment_reference' => 'Payment Reference',
                'payment_method' => 'Payment Method',
                'receipt' => 'Receipt/Invoice',
                'additional_details' => 'Additional Details/Notes',
            ],
            'placeholders' => [
                'select_item' => 'Select item',
                'auto_filled' => 'Auto-filled',
                'reference' => 'REF-001',
                'additional_details' => 'Add any additional details about this expense item...'
            ],
            'remove' => 'Remove item',
            'line_total' => 'Line Total:',
            'empty' => [
                'title' => 'No items added yet.',
                'subtitle' => 'Click "Add Item" to start adding expense items.',
            ],
        ],
        'totals' => [
            'grand_total' => 'Grand Total:',
        ],
        'notes' => [
            'heading' => 'General Notes',
            'description_label' => 'General Description/Notes',
            'description_placeholder' => 'Add any general notes about this expense transaction...'
        ],
        'actions' => [
            'cancel' => 'Cancel',
            'submit' => 'Submit Expense',
        ],
        'payment_methods' => [
            'placeholder' => 'Select method',
            'cash' => 'Cash',
            'credit_card' => 'Credit Card',
            'debit_card' => 'Debit Card',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'mobile_money' => 'Mobile Money',
            'other' => 'Other',
        ],
        'js' => [
            'item_number_prefix' => 'Item #',
            'items_required' => 'Please add at least one item to the expense.',
        ],
    ],
];
