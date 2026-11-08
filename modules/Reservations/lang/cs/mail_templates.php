<?php

return [
    'confirmation' => [
        'label' => 'Potvrzení rezervace',
        'subject' => 'Rezervace {booking_number}: {booking_title}',
        'body' => "Dobrý den {customer_name},\n\nděkujeme za rezervaci.\n\n**{booking_title}**\\\n{booking_date}\\\n{booking_resource}\n\n{amount_due}\n\nDetail, přidání do kalendáře a zrušení:\\\n{booking_url}\n\nS pozdravem\\\n{shop_name}",
    ],
    'reminder' => [
        'label' => 'Připomínka rezervace',
        'subject' => 'Připomínáme: {booking_title} {booking_date}',
        'body' => "Dobrý den {customer_name},\n\npřipomínáme vaši rezervaci:\n\n**{booking_title}**\\\n{booking_date}\\\n{booking_resource}\n\nPokud nemůžete přijít, zrušte ji prosím: {booking_url}\n\nS pozdravem\\\n{shop_name}",
    ],
    'cancelled' => [
        'label' => 'Zrušení rezervace',
        'subject' => 'Rezervace {booking_number} byla zrušena',
        'body' => "Dobrý den {customer_name},\n\nrezervace **{booking_title}** ({booking_date}) byla zrušena.\n\nS pozdravem\\\n{shop_name}",
    ],
    'notification' => [
        'label' => 'Upozornění obchodu na rezervaci',
        'subject' => 'Rezervace {booking_number}: {booking_status}',
        'body' => "Rezervace {booking_number} – {booking_status}\n\n**{booking_title}**\\\n{booking_date}\\\n{booking_resource}\n\nZákazník: {customer_name}\n\n{amount_due}",
    ],
];
