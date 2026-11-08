<?php

return [
    'newsletter_confirm' => [
        'label' => 'Potvrdenie odberu noviniek',
        'subject' => 'Potvrďte odber noviniek – {shop_name}',
        'body' => "Dobrý deň,\n\nďakujeme za záujem o novinky z obchodu {shop_name}. Odber potvrdíte kliknutím na odkaz:\n\n{confirm_url}\n\nAk ste sa neprihlásili vy, e-mail môžete ignorovať.\n\nS pozdravom\\\n{shop_name}",
    ],
    'abandoned_cart' => [
        'label' => 'Opustený košík',
        'subject' => 'Zabudli ste niečo v košíku?',
        'body' => "Dobrý deň,\n\nv košíku vám zostali tieto produkty:\n\n{cart_items}\n\nObjednávku dokončíte tu: {cart_url}\n\nS pozdravom\\\n{shop_name}",
    ],
];
