<?php

return [
    'create' => [
        'page_title' => 'Create Purchase Order - Restaurant Management',
        'title' => 'Create Purchase Order',
        'description' => 'Generate a new purchase order for approved requisitions',
        'back_to_dashboard' => 'Back to Dashboard',
        'link_requisition' => [
            'label' => 'Link to Requisition (Optional)',
            'standalone_option' => 'Create standalone purchase order',
            'hint' => 'Select an approved requisition to auto-populate items, or create a standalone order',
            'vendors_from_requisition' => 'Vendors From Requisition',
            'vendors_summary' => 'Items: :items • Qty: :quantity',
            'approved_fallback' => 'Approved Requisition',
            'approved_suffix' => '(Approved)',
            'po_exists' => '— PO Exists: :number',
        ],
        'supplier_info' => [
            'heading' => 'Supplier Information',
            'supplier_label' => 'Supplier',
            'assign_label' => 'Assign to Purchaser',
            'select_supplier' => 'Select a supplier',
            'select_purchaser' => 'Select purchaser',
            'taken_from_requisition' => 'Vendors will be taken from the requisition items.',
            'field_disabled' => 'This field is disabled when using an approved requisition.',
        ],
        'order_items' => [
            'heading' => 'Order Items',
            'locked_notice' => 'Items are derived from the approved requisition and cannot be edited here.\nPlease select an approved requisition above to preview grouped items below.',
            'preview' => [
                'title' => 'PO Preview from Requisition #:id',
                'chef' => 'Chef:',
                'requested_for' => 'Requested For:',
                'subtotal_label' => 'Subtotal',
                'vendor_subtotal' => 'Vendor Subtotal',
                'group_summary' => ':items item(s) • Total Qty: :quantity',
                'table_headers' => [
                    'item' => 'Item',
                    'qty' => 'Qty',
                    'unit' => 'UoM',
                    'unit_price' => 'Unit Price',
                    'line_total' => 'Line Total',
                ],
                'totals' => [
                    'total_items' => 'Total Items',
                    'total_quantity' => 'Total Quantity',
                    'grand_subtotal' => 'Grand Subtotal',
                ],
            ],
        ],
        'delivery' => [
            'requested_date' => 'Requested Delivery Date',
            'payment_terms' => 'Payment Terms',
            'payment_terms_placeholder' => 'Select payment terms',
            'payment_terms_options' => [
                'net_7' => 'Net 7 days',
                'net_15' => 'Net 15 days',
                'net_30' => 'Net 30 days',
                'net_60' => 'Net 60 days',
                'cod' => 'Cash on Delivery',
                'advance' => 'Advance Payment',
            ],
        ],
        'instructions' => [
            'delivery' => 'Delivery Instructions',
            'delivery_placeholder' => 'Enter any special delivery instructions...',
            'notes' => 'Internal Notes',
            'notes_placeholder' => 'Enter any internal notes...'
        ],
        'alerts' => [
            'duplicate_po' => 'A purchase order already exists for this requisition. Creating another is disabled.',
        ],
        'email' => [
            'send_to_supplier' => 'Send PO to supplier via email',
        ],
        'actions' => [
            'cancel' => 'Cancel',
            'save_draft' => 'Save as Draft',
            'submit' => 'Create Purchase Order',
        ],
        'info_panel' => [
            'tip_prefix' => 'Tip:',
            'message' => 'Purchase orders can be linked to approved requisitions for better tracking. All PO activities are automatically logged in the audit trail.',
        ],
        'js' => [
            'unknown_vendor' => 'Unknown Vendor',
        ],
    ],
];
