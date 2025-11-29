<?php

return [
    'create' => [
        'title' => 'Ongeza Gharama',
        'description' => 'Rekodi miamala ya gharama yenye vipengee vingi',
        'back_to_dashboard' => 'Rudi kwenye Dashibodi',
        'expense_date' => 'Tarehe ya Gharama',
        'items' => [
            'heading' => 'Vipengee vya Gharama',
            'add_button' => 'Ongeza Kipengee',
            'labels' => [
                'item' => 'Kipengee',
                'vendor' => 'Muuzaji',
                'quantity' => 'Kiasi',
                'unit_price' => 'Bei kwa Kipimo',
                'payment_reference' => 'Marejeleo ya Malipo',
                'payment_method' => 'Njia ya Malipo',
                'receipt' => 'Risiti/Ankara',
                'additional_details' => 'Maelezo ya Ziada/Kumbukumbu',
            ],
            'placeholders' => [
                'select_item' => 'Chagua kipengee',
                'auto_filled' => 'Imejazwa kiotomatiki',
                'reference' => 'REF-001',
                'additional_details' => 'Ongeza maelezo ya ziada kuhusu kipengee hiki cha gharama...'
            ],
            'remove' => 'Ondoa kipengee',
            'line_total' => 'Jumla ya Mstari:',
            'empty' => [
                'title' => 'Hakuna vipengee vilivyoongezwa bado.',
                'subtitle' => 'Bofya "Ongeza Kipengee" ili kuanza kuongeza gharama.',
            ],
        ],
        'totals' => [
            'grand_total' => 'Jumla Kuu:',
        ],
        'notes' => [
            'heading' => 'Maelezo ya Jumla',
            'description_label' => 'Maelezo ya Jumla/Kumbukumbu',
            'description_placeholder' => 'Ongeza maelezo ya jumla kuhusu muamala huu wa gharama...'
        ],
        'actions' => [
            'cancel' => 'Ghairi',
            'submit' => 'Wasilisha Gharama',
        ],
        'payment_methods' => [
            'placeholder' => 'Chagua njia',
            'cash' => 'Fedha Taslimu',
            'credit_card' => 'Kadi ya Mkopo',
            'debit_card' => 'Kadi ya Malipo',
            'bank_transfer' => 'Uhamisho wa Benki',
            'check' => 'Hundi',
            'mobile_money' => 'Fedha kwa Simu',
            'other' => 'Nyingine',
        ],
        'js' => [
            'item_number_prefix' => 'Kipengee #',
            'items_required' => 'Tafadhali ongeza angalau kipengee kimoja kwenye gharama.',
        ],
    ],
];
