<?php

return [
    'section_keys' => 'Prístupové údaje',
    'section_keys_help' => 'Nájdete ich v klientskej sekcii Packeta → Môj účet → API. Packeta nemá testovacie prostredie, poplatok vzniká až pri podaní zásielky.',
    'api_key' => 'API kľúč',
    'api_key_help' => '16 znakov, používa sa pre mapu výdajných miest.',
    'api_password' => 'API heslo',
    'sender' => 'Označenie odosielateľa',
    'sender_help' => 'Názov odosielateľa (eshop) z klientskej sekcie, ak ich máte viac.',
    'label_format' => 'Formát štítkov',
    'country' => 'Krajina výdajných miest',
    'point_groups' => 'Ponúkať',
    'groups' => ['points' => 'Výdajné miesta', 'zbox' => 'Z-BOXy'],
    'carrier_id' => 'ID dopravcu pre doručenie domov',
    'carrier_id_help' => 'Napr. 131 = Packeta Home SK, 106 = Packeta Home CZ (zoznam dopravcov v klientskej sekcii). Iba pri doručení na adresu.',
    'missing_password' => 'Packeta: chýba API heslo (Nastavenia → Packeta).',
    'missing_carrier_id' => 'Packeta: pri doručení na adresu chýba ID dopravcu v nastaveniach spôsobu dopravy.',
    'unavailable' => 'Packeta: služba momentálne neodpovedá, skúste to neskôr.',
    'invalid_response' => 'Packeta: neplatná odpoveď servera.',
];
