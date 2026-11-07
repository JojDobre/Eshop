<?php

return [
    'navigation' => 'DPH',
    'settings' => [
        'vat_payer' => 'Jsem plátce DPH',
        'vat_payer_help' => 'Pokud nejste plátce DPH, ceny se uvádějí bez rozpisu DPH a na dokladech bude „Není plátce DPH".',
        'prices_include_vat' => 'Ceny zadávám včetně DPH',
        'prices_include_vat_help' => 'Když je vypnuto, ceny produktů zadáváte bez DPH a obchod k nim připočte DPH podle sazby produktu.',
    ],
    'rates' => [
        'label' => 'Sazba DPH',
        'plural' => 'Sazby DPH',
        'name' => 'Název',
        'rate' => 'Sazba',
        'position' => 'Pořadí',
        'is_default' => 'Výchozí',
        'is_default_help' => 'Použije se pro produkty bez vybrané sazby a pro dopravu a platbu.',
    ],
];
