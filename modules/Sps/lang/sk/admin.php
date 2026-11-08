<?php

return [
    'section_keys' => 'Prístup k WebShip API',
    'section_keys_help' => 'Rovnaké meno a heslo ako do portálu webship.sps-sro.sk. Prístup k API vám musí zapnúť obchodník SPS. Odosielateľ je podľa zmluvy so SPS.',
    'username' => 'Používateľ',
    'password' => 'Heslo',
    'section_shipments' => 'Zásielky',
    'service' => 'Služba',
    'service_help' => 'Kód služby SPS podľa zmluvy, napr. expres.',
    'notify' => 'Avízo príjemcovi',
    'notify_types' => [0 => 'Bez avíza', 1 => 'E-mail', 2 => 'SMS', 3 => 'E-mail aj SMS'],
    'insure' => 'Poistiť na hodnotu objednávky',
    'insure_help' => 'Poistenie je spoplatnené podľa cenníka SPS.',
    'cod_iban' => 'IBAN pre dobierky',
    'cod_iban_help' => 'Prázdne = IBAN z Nastavenia → Firma (alebo účet zo zmluvy so SPS).',
    'missing_credentials' => 'SPS: chýba používateľ alebo heslo (Nastavenia → SPS).',
    'unavailable' => 'SPS: služba momentálne neodpovedá, skúste to neskôr.',
    'invalid_response' => 'SPS: neplatná odpoveď servera.',
    'label_only_on_create' => 'SPS: štítok sa ukladá pri vytvorení zásielky. Ak chýba, vytlačte ho v portáli WebShip.',
];
