<?php

return [
    'navigation' => 'Payments',
    'label' => 'Payment method',
    'plural' => 'Payment methods',
    'name' => 'Name',
    'type' => 'Type',
    'types' => [
        'bank_transfer' => 'Bank transfer',
        'cash_on_delivery' => 'Cash on delivery',
        'cash' => 'Pay on pickup',
        'card' => 'Online card payment',
    ],
    'description' => 'Description',
    'description_help' => 'Shown to the customer when choosing payment.',
    'instructions' => 'Instructions after ordering',
    'instructions_help' => 'Optional. Shown after the order is placed and in the confirmation e-mail.',
    'fee' => 'Fee',
    'position' => 'Order',
    'shipping_methods' => 'Allowed shipping',
    'shipping_methods_help' => 'If nothing is selected, the payment is available with every shipping method.',
    'all_shipping' => 'All',
    'is_active' => 'Active',
];
