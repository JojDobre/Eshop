<?php

return [
    'section_keys' => 'Přístupové údaje',
    'section_keys_help' => 'Najdete je v klientské sekci Packeta → Můj účet → API. Packeta nemá testovací prostředí, poplatek vzniká až při podání zásilky.',
    'api_key' => 'API klíč',
    'api_key_help' => '16 znaků, používá se pro mapu výdejních míst.',
    'api_password' => 'API heslo',
    'sender' => 'Označení odesílatele',
    'sender_help' => 'Název odesílatele (eshop) z klientské sekce, pokud jich máte více.',
    'label_format' => 'Formát štítků',
    'country' => 'Země výdejních míst',
    'point_groups' => 'Nabízet',
    'groups' => ['points' => 'Výdejní místa', 'zbox' => 'Z-BOXy'],
    'carrier_id' => 'ID dopravce pro doručení domů',
    'carrier_id_help' => 'Např. 106 = Packeta Home CZ, 131 = Packeta Home SK (seznam dopravců v klientské sekci). Jen při doručení na adresu.',
    'missing_password' => 'Packeta: chybí API heslo (Nastavení → Packeta).',
    'missing_carrier_id' => 'Packeta: při doručení na adresu chybí ID dopravce v nastavení způsobu dopravy.',
    'unavailable' => 'Packeta: služba momentálně neodpovídá, zkuste to později.',
    'invalid_response' => 'Packeta: neplatná odpověď serveru.',
];
