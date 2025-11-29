<?php

return [
    'title' => 'Dashibodi',
    'welcome' => 'Karibu tena, :name',
    'date_label' => 'Tarehe',
    'cards' => [
        'sales' => [
            'title' => 'Mauzo ya Leo',
            'transactions' => '{0} Hakuna miamala|{1} Muamala :count|[2,*] Miamala :count',
        ],
        'expenses' => [
            'title' => 'Matumizi ya Leo',
            'items' => '{0} Hakuna vipengee|{1} Kipengee :count|[2,*] Vipengee :count',
        ],
        'profit' => [
            'title' => 'Faida ya Leo',
            'margin' => 'Marijeni:',
        ],
        'approvals' => [
            'title' => 'Idhini Zinazosubiri',
            'requires_attention' => 'Inahitaji uangalizi',
        ],
    ],
    'quick_actions' => [
        'title' => 'Vitendo vya Haraka',
        'requisition' => 'Ombi',
        'purchase_order' => 'Agizo la Ununuzi',
        'add_expense' => 'Ongeza Gharama',
        'view_reports' => 'Tazama Ripoti',
        'manage_payroll' => 'Simamia Mishahara',
        'settings' => 'Mipangilio',
    ],
    'recent_requisitions' => [
        'title' => 'Maombi ya Hivi Karibuni',
        'view_all' => 'Tazama Zote →',
        'empty' => 'Hakuna maombi ya hivi karibuni.',
        'headers' => [
            'id' => 'Kitambulisho',
            'chef' => 'Mpishi',
            'requested_date' => 'Tarehe Iliyoombwa',
            'items' => 'Vipengee',
            'status' => 'Hali',
            'actions' => 'Vitendo',
        ],
        'items_count' => '{0} Hakuna vipengee|{1} Kipengee :count|[2,*] Vipengee :count',
        'status' => [
            'pending' => 'Inasubiri',
            'approved' => 'Imeidhinishwa',
            'rejected' => 'Imekataliwa',
            'default' => ':status',
        ],
        'view' => 'Tazama',
    ],
];
