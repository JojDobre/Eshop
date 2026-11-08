<?php

return [
    'received' => [
        'label' => 'Prijatie odstúpenia alebo reklamácie (zákazník)',
        'subject' => '{request_type} č. {request_number} – potvrdenie prijatia',
        'body' => 'Dobrý deň {customer_name},

potvrdzujeme prijatie žiadosti **{request_type}** č. **{request_number}** k objednávke č. {order_number}.

Tovar: {request_items}

Žiadosť vybavíme najneskôr do **{deadline}**. Tovar nám pošlite na adresu obchodu, ak sme sa nedohodli inak.

[Zobraziť objednávku]({order_url})

S pozdravom\\
{shop_name}',
    ],
    'notification' => [
        'label' => 'Nové odstúpenie alebo reklamácia (obchodník)',
        'subject' => '{request_type} č. {request_number} k objednávke {order_number}',
        'body' => '# {request_type} č. {request_number}

Objednávka č. {order_number}, zákazník {customer_name}.

Tovar: {request_items}

{reason}

Lehota: **{deadline}**

[Otvoriť v administrácii]({admin_url})',
    ],
    'status' => [
        'label' => 'Zmena stavu odstúpenia alebo reklamácie',
        'subject' => '{request_type} č. {request_number}: {request_status}',
        'body' => 'Dobrý deň {customer_name},

stav žiadosti **{request_type}** č. {request_number} sa zmenil na **{request_status}**.

{resolution}

[Zobraziť objednávku]({order_url})

S pozdravom\\
{shop_name}',
    ],
    'deadline_warning' => [
        'label' => 'Upozornenie na lehotu reklamácie',
        'subject' => 'Blíži sa lehota na vybavenie žiadostí',
        'body' => "Týmto žiadostiam sa blíži zákonná lehota na vybavenie:\n\n{requests}\n\nVybavte ich v administrácii: {admin_url}",
    ],
];
