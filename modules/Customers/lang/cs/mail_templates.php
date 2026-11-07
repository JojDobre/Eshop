<?php

return [
    'welcome' => [
        'label' => 'Vítejte (po registraci)',
        'subject' => 'Vítejte v obchodě {shop_name}',
        'body' => 'Dobrý den {customer_name},

děkujeme za registraci. Ve svém účtu uvidíte objednávky a při dalším nákupu se vám adresa vyplní sama.

[Můj účet]({account_url})

S pozdravem\\
{shop_name}',
    ],
    'password_reset' => [
        'label' => 'Obnovení hesla',
        'subject' => 'Nové heslo do obchodu {shop_name}',
        'body' => 'Dobrý den {customer_name},

požádali jste o nové heslo. Nastavíte si ho přes tento odkaz (platí 60 minut):

[Nastavit nové heslo]({reset_url})

Pokud jste o nové heslo nežádali, e-mail ignorujte.

S pozdravem\\
{shop_name}',
    ],
];
