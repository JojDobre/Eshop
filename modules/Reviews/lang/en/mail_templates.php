<?php

return [
    'request' => [
        'label' => 'Review request',
        'subject' => 'How happy are you with order no. {order_number}?',
        'body' => "Hello {customer_name},\n\nthank you for your order. We would be glad if you reviewed the products you bought – it helps other customers:\n\n{review_url}\n\nKind regards\\\n{shop_name}",
    ],
];
