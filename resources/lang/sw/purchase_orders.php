<?php

return [
    'create' => [
        'page_title' => 'Unda Agizo la Ununuzi - Usimamizi wa Mgahawa',
        'title' => 'Unda Agizo la Ununuzi',
        'description' => 'Tengeneza agizo jipya la ununuzi kwa maombi yaliyoidhinishwa',
        'back_to_dashboard' => 'Rudi kwenye Dashibodi',
        'link_requisition' => [
            'label' => 'Unganisha na Ombi (Hiari)',
            'standalone_option' => 'Unda agizo la ununuzi lisilo na uhusiano',
            'hint' => 'Chagua ombi lililoidhinishwa kujaza vipengee kiotomatiki, au unda agizo huru',
            'vendors_from_requisition' => 'Wauzaji Kutoka kwenye Ombi',
            'vendors_summary' => 'Vipengee: :items • Kiasi: :quantity',
            'approved_fallback' => 'Ombi Lililoidhinishwa',
            'approved_suffix' => '(Imeidhinishwa)',
            'po_exists' => '— PO Ipo: :number',
        ],
        'supplier_info' => [
            'heading' => 'Taarifa za Muuzaji',
            'supplier_label' => 'Muuzaji',
            'assign_label' => 'Mkabidhi Mnunuzi',
            'select_supplier' => 'Chagua muuzaji',
            'select_purchaser' => 'Chagua mnunuzi',
            'taken_from_requisition' => 'Wauzaji watatokana na vipengee vya ombi.',
            'field_disabled' => 'Sehemu hii imezimwa unapochagua ombi lililoidhinishwa.',
        ],
        'order_items' => [
            'heading' => 'Vipengee vya Agizo',
            'locked_notice' => "Vipengee vinatokana na ombi lililoidhinishwa na haviwezi kuhaririwa hapa.\nTafadhali chagua ombi lililoidhinishwa hapo juu ili kuona makundi ya vipengee hapa chini.",
            'preview' => [
                'title' => 'Hakiki ya PO kutoka Ombi #:id',
                'chef' => 'Mpishi:',
                'requested_for' => 'Tarehe Iliyoombwa:',
                'subtotal_label' => 'Jumla Ndogo',
                'vendor_subtotal' => 'Jumla Ndogo ya Muuzaji',
                'group_summary' => 'Vipengee: :items • Jumla ya Kiasi: :quantity',
                'table_headers' => [
                    'item' => 'Kipengee',
                    'qty' => 'Kiasi',
                    'unit' => 'Kipimo',
                    'unit_price' => 'Bei kwa Kipimo',
                    'line_total' => 'Jumla ya Mstari',
                ],
                'totals' => [
                    'total_items' => 'Jumla ya Vipengee',
                    'total_quantity' => 'Jumla ya Kiasi',
                    'grand_subtotal' => 'Jumla Ndogo Kuu',
                ],
            ],
        ],
        'delivery' => [
            'requested_date' => 'Tarehe ya Uwasilishaji Iliyoombwa',
            'payment_terms' => 'Masharti ya Malipo',
            'payment_terms_placeholder' => 'Chagua masharti ya malipo',
            'payment_terms_options' => [
                'net_7' => 'Malipo ndani ya siku 7',
                'net_15' => 'Malipo ndani ya siku 15',
                'net_30' => 'Malipo ndani ya siku 30',
                'net_60' => 'Malipo ndani ya siku 60',
                'cod' => 'Malipo Wakati wa Uwasilishaji',
                'advance' => 'Malipo ya Awali',
            ],
        ],
        'instructions' => [
            'delivery' => 'Maelekezo ya Uwasilishaji',
            'delivery_placeholder' => 'Ingiza maelekezo yoyote maalum ya uwasilishaji...',
            'notes' => 'Maelezo ya Ndani',
            'notes_placeholder' => 'Ingiza maelezo yoyote ya ndani...'
        ],
        'alerts' => [
            'duplicate_po' => 'Agizo la ununuzi tayari lipo kwa ombi hili. Kuunda jingine kumekataliwa.',
        ],
        'email' => [
            'send_to_supplier' => 'Tuma PO kwa muuzaji kupitia barua pepe',
        ],
        'actions' => [
            'cancel' => 'Ghairi',
            'save_draft' => 'Hifadhi kama Rasimu',
            'submit' => 'Unda Agizo la Ununuzi',
        ],
        'info_panel' => [
            'tip_prefix' => 'Dokezo:',
            'message' => 'Maagizo ya ununuzi yanaweza kuunganishwa na maombi yaliyoidhinishwa ili kurahisisha ufuatiliaji. Shughuli zote za PO husajiliwa kiotomatiki kwenye kumbukumbu ya ukaguzi.',
        ],
        'js' => [
            'unknown_vendor' => 'Muuzaji Hajulikani',
        ],
    ],
];
