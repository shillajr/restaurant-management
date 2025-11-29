<?php

return [
    'title' => 'Dashboard',
    'welcome' => 'Welcome back, :name',
    'date_label' => 'Date',
    'cards' => [
        'sales' => [
            'title' => "Today's Sales",
            'transactions' => '{0} No transactions|{1} :count transaction|[2,*] :count transactions',
        ],
        'expenses' => [
            'title' => "Today's Expenses",
            'items' => '{0} No items|{1} :count item|[2,*] :count items',
        ],
        'profit' => [
            'title' => "Today's Profit",
            'margin' => 'Margin:',
        ],
        'approvals' => [
            'title' => 'Pending Approvals',
            'requires_attention' => 'Requires attention',
        ],
    ],
    'quick_actions' => [
        'title' => 'Quick Actions',
        'requisition' => 'Requisition',
        'purchase_order' => 'Purchase Order',
        'add_expense' => 'Add Expense',
        'view_reports' => 'View Reports',
        'manage_payroll' => 'Manage Payroll',
        'settings' => 'Settings',
    ],
    'recent_requisitions' => [
        'title' => 'Recent Requisitions',
        'view_all' => 'View All →',
        'empty' => 'No recent requisitions found.',
        'headers' => [
            'id' => 'ID',
            'chef' => 'Chef',
            'requested_date' => 'Requested Date',
            'items' => 'Items',
            'status' => 'Status',
            'actions' => 'Actions',
        ],
        'items_count' => '{0} No items|{1} :count item|[2,*] :count items',
        'status' => [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'default' => ':status',
        ],
        'view' => 'View',
    ],
];
