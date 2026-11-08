<?php

return [
    'confirmation' => [
        'label' => 'Potvrdenie rezervácie',
        'subject' => 'Rezervácia {booking_number}: {booking_title}',
        'body' => "Dobrý deň {customer_name},\n\nďakujeme za rezerváciu.\n\n**{booking_title}**\\\n{booking_date}\\\n{booking_resource}\n\n{amount_due}\n\nDetail, pridanie do kalendára a zrušenie:\\\n{booking_url}\n\nS pozdravom\\\n{shop_name}",
    ],
    'reminder' => [
        'label' => 'Pripomienka rezervácie',
        'subject' => 'Pripomíname: {booking_title} {booking_date}',
        'body' => "Dobrý deň {customer_name},\n\npripomíname vašu rezerváciu:\n\n**{booking_title}**\\\n{booking_date}\\\n{booking_resource}\n\nAk nemôžete prísť, zrušte ju, prosím: {booking_url}\n\nS pozdravom\\\n{shop_name}",
    ],
    'cancelled' => [
        'label' => 'Zrušenie rezervácie',
        'subject' => 'Rezervácia {booking_number} bola zrušená',
        'body' => "Dobrý deň {customer_name},\n\nrezervácia **{booking_title}** ({booking_date}) bola zrušená.\n\nS pozdravom\\\n{shop_name}",
    ],
    'notification' => [
        'label' => 'Upozornenie obchodu na rezerváciu',
        'subject' => 'Rezervácia {booking_number}: {booking_status}',
        'body' => "Rezervácia {booking_number} – {booking_status}\n\n**{booking_title}**\\\n{booking_date}\\\n{booking_resource}\n\nZákazník: {customer_name}\n\n{amount_due}",
    ],
];
