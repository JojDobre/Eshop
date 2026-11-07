<?php

return [
    'navigation' => 'DPH',
    'settings' => [
        'vat_payer' => 'Som platiteľ DPH',
        'vat_payer_help' => 'Ak nie ste platiteľ DPH, ceny sa uvádzajú bez rozpisu DPH a na dokladoch bude „Nie je platiteľ DPH".',
        'prices_include_vat' => 'Ceny zadávam vrátane DPH',
        'prices_include_vat_help' => 'Keď je vypnuté, ceny produktov zadávate bez DPH a obchod k nim pripočíta DPH podľa sadzby produktu.',
    ],
    'rates' => [
        'label' => 'Sadzba DPH',
        'plural' => 'Sadzby DPH',
        'name' => 'Názov',
        'rate' => 'Sadzba',
        'position' => 'Poradie',
        'is_default' => 'Predvolená',
        'is_default_help' => 'Použije sa pre produkty bez vybranej sadzby a pre dopravu a platbu.',
    ],
];
