<?php

return [
    'confirmation' => [
        'label' => 'Potvrdenie objednávky (zákazník)',
        'subject' => 'Potvrdenie objednávky č. {order_number}',
        'body' => <<<'MD'
# Ďakujeme za objednávku

Dobrý deň {customer_name},

prijali sme vašu objednávku č. **{order_number}**. O ďalšom postupe vás budeme informovať.

{order_items}

{payment_details}

{shipping_details}

[Zobraziť objednávku]({order_url})

S pozdravom\
{shop_name}
MD,
    ],
    'notification' => [
        'label' => 'Nová objednávka (obchodník)',
        'subject' => 'Nová objednávka č. {order_number}',
        'body' => <<<'MD'
# Nová objednávka č. {order_number}

**{customer_name}** ({customer_email}, {customer_phone}) – spolu **{order_total}**.

{order_items}

Doprava: {shipping_method}, platba: {payment_method}.

{customer_note}

[Otvoriť v administrácii]({admin_url})
MD,
    ],
    'status_processing' => [
        'label' => 'Objednávka sa spracúva',
        'subject' => 'Objednávka č. {order_number} sa spracúva',
        'body' => "Dobrý deň {customer_name},\n\nvašu objednávku č. **{order_number}** sme začali spracúvať.\n\n[Zobraziť objednávku]({order_url})\n\nS pozdravom\\\n{shop_name}",
    ],
    'status_ready_for_pickup' => [
        'label' => 'Objednávka je pripravená na vyzdvihnutie',
        'subject' => 'Objednávka č. {order_number} je pripravená na vyzdvihnutie',
        'body' => "Dobrý deň {customer_name},\n\nvaša objednávka č. **{order_number}** je pripravená na vyzdvihnutie.\n\n{shipping_details}\n\n[Zobraziť objednávku]({order_url})\n\nS pozdravom\\\n{shop_name}",
    ],
    'status_shipped' => [
        'label' => 'Objednávka bola odoslaná',
        'subject' => 'Objednávka č. {order_number} bola odoslaná',
        'body' => "Dobrý deň {customer_name},\n\nvašu objednávku č. **{order_number}** sme odovzdali na doručenie.\n\n{shipping_details}\n\n[Zobraziť objednávku]({order_url})\n\nS pozdravom\\\n{shop_name}",
    ],
    'status_completed' => [
        'label' => 'Objednávka je vybavená',
        'subject' => 'Objednávka č. {order_number} je vybavená',
        'body' => "Dobrý deň {customer_name},\n\nvaša objednávka č. **{order_number}** je vybavená. Ďakujeme za nákup.\n\nS pozdravom\\\n{shop_name}",
    ],
    'status_cancelled' => [
        'label' => 'Objednávka bola zrušená',
        'subject' => 'Objednávka č. {order_number} bola zrušená',
        'body' => "Dobrý deň {customer_name},\n\nvaša objednávka č. **{order_number}** bola zrušená. Ak máte otázky, odpovedzte na tento e-mail.\n\nS pozdravom\\\n{shop_name}",
    ],
];
