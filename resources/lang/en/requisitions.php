<?php

return [
    'create' => [
        'title' => 'Create Chef Requisition',
        'description' => 'Submit your ingredient and supply requests',
        'requested_for_date' => 'Requested For Date',
        'items_section' => [
            'heading' => 'Items',
            'instructions' => 'At least one item required',
            'table' => [
                'item' => 'Item',
                'vendor' => 'Vendor',
                'price' => 'Price (:currency)',
                'quantity' => 'Quantity',
                'unit' => 'UoM',
                'line_total' => 'Line Total',
                'action' => 'Action',
                'select_placeholder' => 'Select item...',
            ],
            'price_modified' => 'Price modified',
            'remove_tooltip' => 'Remove item',
            'add_button' => 'Add Another Item',
        ],
        'summary' => [
            'heading' => 'Requisition Summary',
            'total_items' => 'Total Items:',
            'total_quantity' => 'Total Quantity:',
            'modified_prices' => 'Modified Prices:',
            'subtotal' => 'Subtotal:',
            'taxes' => 'Tax/Charges:',
            'grand_total' => 'Grand Total:',
            'was' => 'Was:',
        ],
        'notes' => [
            'label' => 'Additional Notes',
            'placeholder' => 'Add any special instructions, quality requirements, or delivery instructions...'
        ],
        'actions' => [
            'cancel' => 'Cancel',
            'submit' => 'Submit Requisition',
        ],
        'tips' => [
            'heading' => 'Tips for submitting requisitions',
            'list' => [
                'Select items from the registered Item Master',
                'Vendor and default price are automatically populated',
                'You can edit prices if needed - changes will be tracked',
                'Request items at least 2 days in advance',
                'Review the summary before submitting',
            ],
        ],
    ],
];
