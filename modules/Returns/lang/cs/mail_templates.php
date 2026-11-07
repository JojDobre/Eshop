<?php

return [
    'received' => [
        'label' => 'Přijetí odstoupení nebo reklamace (zákazník)',
        'subject' => '{request_type} č. {request_number} – potvrzení přijetí',
        'body' => 'Dobrý den {customer_name},

potvrzujeme přijetí žádosti **{request_type}** č. **{request_number}** k objednávce č. {order_number}.

Zboží: {request_items}

Žádost vyřídíme nejpozději do **{deadline}**. Zboží nám pošlete na adresu obchodu, pokud jsme se nedohodli jinak.

[Zobrazit objednávku]({order_url})

S pozdravem\\
{shop_name}',
    ],
    'notification' => [
        'label' => 'Nové odstoupení nebo reklamace (obchodník)',
        'subject' => '{request_type} č. {request_number} k objednávce {order_number}',
        'body' => '# {request_type} č. {request_number}

Objednávka č. {order_number}, zákazník {customer_name}.

Zboží: {request_items}

{reason}

Lhůta: **{deadline}**

[Otevřít v administraci]({admin_url})',
    ],
    'status' => [
        'label' => 'Změna stavu odstoupení nebo reklamace',
        'subject' => '{request_type} č. {request_number}: {request_status}',
        'body' => 'Dobrý den {customer_name},

stav žádosti **{request_type}** č. {request_number} se změnil na **{request_status}**.

{resolution}

[Zobrazit objednávku]({order_url})

S pozdravem\\
{shop_name}',
    ],
];
