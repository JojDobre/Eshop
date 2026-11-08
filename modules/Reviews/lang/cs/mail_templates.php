<?php

return [
    'request' => [
        'label' => 'Žádost o hodnocení',
        'subject' => 'Jak jste spokojeni s nákupem č. {order_number}?',
        'body' => "Dobrý den {customer_name},\n\nděkujeme za váš nákup. Budeme rádi, když ohodnotíte zakoupené produkty – pomůžete tím dalším zákazníkům:\n\n{review_url}\n\nS pozdravem\\\n{shop_name}",
    ],
];
