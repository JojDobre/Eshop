<?php

return [
    'newsletter_confirm' => [
        'label' => 'Potvrzení odběru novinek',
        'subject' => 'Potvrďte odběr novinek – {shop_name}',
        'body' => "Dobrý den,\n\nděkujeme za zájem o novinky z obchodu {shop_name}. Odběr potvrdíte kliknutím na odkaz:\n\n{confirm_url}\n\nPokud jste se nepřihlásili vy, e-mail můžete ignorovat.\n\nS pozdravem\\\n{shop_name}",
    ],
    'abandoned_cart' => [
        'label' => 'Opuštěný košík',
        'subject' => 'Zapomněli jste něco v košíku?',
        'body' => "Dobrý den,\n\nv košíku vám zůstaly tyto produkty:\n\n{cart_items}\n\nObjednávku dokončíte zde: {cart_url}\n\nS pozdravem\\\n{shop_name}",
    ],
];
