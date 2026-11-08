<?php

return [
    'section_keys' => 'Přístup k WebShip API',
    'section_keys_help' => 'Stejné jméno a heslo jako do portálu webship.sps-sro.sk. Přístup k API vám musí zapnout obchodník SPS. Odesílatel je podle smlouvy se SPS.',
    'username' => 'Uživatel',
    'password' => 'Heslo',
    'section_shipments' => 'Zásilky',
    'service' => 'Služba',
    'service_help' => 'Kód služby SPS podle smlouvy, např. expres.',
    'notify' => 'Avízo příjemci',
    'notify_types' => [0 => 'Bez avíza', 1 => 'E-mail', 2 => 'SMS', 3 => 'E-mail i SMS'],
    'insure' => 'Pojistit na hodnotu objednávky',
    'insure_help' => 'Pojištění je zpoplatněno podle ceníku SPS.',
    'cod_iban' => 'IBAN pro dobírky',
    'cod_iban_help' => 'Prázdné = IBAN z Nastavení → Firma (nebo účet ze smlouvy se SPS).',
    'missing_credentials' => 'SPS: chybí uživatel nebo heslo (Nastavení → SPS).',
    'unavailable' => 'SPS: služba momentálně neodpovídá, zkuste to později.',
    'invalid_response' => 'SPS: neplatná odpověď serveru.',
    'label_only_on_create' => 'SPS: štítek se ukládá při vytvoření zásilky. Pokud chybí, vytiskněte ho v portálu WebShip.',
];
