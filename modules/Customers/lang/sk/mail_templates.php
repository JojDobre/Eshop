<?php

return [
    'welcome' => [
        'label' => 'Vitajte (po registrácii)',
        'subject' => 'Vitajte v obchode {shop_name}',
        'body' => 'Dobrý deň {customer_name},

ďakujeme za registráciu. Vo svojom účte uvidíte objednávky a pri ďalšom nákupe sa vám adresa vyplní sama.

[Môj účet]({account_url})

S pozdravom\\
{shop_name}',
    ],
    'password_reset' => [
        'label' => 'Obnovenie hesla',
        'subject' => 'Nové heslo do obchodu {shop_name}',
        'body' => 'Dobrý deň {customer_name},

požiadali ste o nové heslo. Nastavíte si ho cez tento odkaz (platí 60 minút):

[Nastaviť nové heslo]({reset_url})

Ak ste o nové heslo nežiadali, e-mail ignorujte.

S pozdravom\\
{shop_name}',
    ],
];
