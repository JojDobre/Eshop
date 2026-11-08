<?php

return [
    'newsletter_confirm' => [
        'label' => 'Newsletter confirmation',
        'subject' => 'Confirm your subscription – {shop_name}',
        'body' => "Hello,\n\nthank you for your interest in news from {shop_name}. Confirm your subscription by clicking the link:\n\n{confirm_url}\n\nIf you did not sign up, you can ignore this e-mail.\n\nKind regards\\\n{shop_name}",
    ],
    'abandoned_cart' => [
        'label' => 'Abandoned cart',
        'subject' => 'Did you forget something in your cart?',
        'body' => "Hello,\n\nthese products are still in your cart:\n\n{cart_items}\n\nFinish your order here: {cart_url}\n\nKind regards\\\n{shop_name}",
    ],
];
