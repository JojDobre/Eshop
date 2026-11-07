<?php

return [
    'navigation' => 'Shipping',
    'label' => 'Shipping method',
    'plural' => 'Shipping methods',
    'name' => 'Name',
    'type' => 'Type',
    'types' => [
        'pickup' => 'In-store pickup',
        'delivery' => 'Delivery to address',
    ],
    'description' => 'Description',
    'description_help' => 'Shown to the customer when choosing shipping.',
    'pickup_details' => 'Address and opening hours',
    'pickup_details_help' => 'The customer sees them after ordering and in the confirmation e-mail.',
    'price' => 'Price',
    'free_from' => 'Free from order total',
    'free_from_help' => 'Optional. Shipping is free when the order reaches this amount.',
    'weight_rates' => 'Prices by weight',
    'weight_rates_help' => 'Optional. Price by order weight; orders heavier than the last band will not see this method. Without bands the base price applies.',
    'up_to' => 'Up to weight',
    'add_weight_rate' => 'Add band',
    'position' => 'Order',
    'is_active' => 'Active',
];
