<?php

return [
    'product' => 'Product',
    'quantity' => 'Qty',
    'price' => 'Price',
    'discount' => 'Discount (:code)',
    'pay_online' => 'Pay the order by card',
    'qr_message' => 'Order :number',
    'total' => 'Total',
    'confirmation' => [
        'subject' => 'Order confirmation no. :number',
        'heading' => 'Thank you for your order',
        'intro' => 'Hello :name, we have received your order no. :number. We will keep you informed about the next steps.',
        'view_order' => 'View order',
        'thanks' => 'Kind regards',
    ],
    'bank' => [
        'heading' => 'Payment details',
        'account' => 'Account number (IBAN)',
        'variable_symbol' => 'Payment reference',
        'amount' => 'Amount',
    ],
    'notification' => [
        'subject' => 'New order no. :number',
        'heading' => 'New order no. :number',
        'shipping_payment' => 'Shipping: :shipping, payment: :payment.',
        'open' => 'Open in administration',
    ],
];
