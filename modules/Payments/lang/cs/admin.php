<?php

return [
    'navigation' => 'Platby',
    'label' => 'Způsob platby',
    'plural' => 'Způsoby platby',
    'name' => 'Název',
    'type' => 'Typ',
    'types' => [
        'bank_transfer' => 'Bankovní převod',
        'cash_on_delivery' => 'Dobírka',
        'cash' => 'Platba při osobním odběru',
        'card' => 'Platba kartou online',
    ],
    'description' => 'Popis',
    'description_help' => 'Zobrazí se zákazníkovi při výběru platby.',
    'instructions' => 'Pokyny po objednávce',
    'instructions_help' => 'Nepovinné. Zobrazí se po odeslání objednávky a v potvrzovacím e-mailu.',
    'fee' => 'Poplatek',
    'position' => 'Pořadí',
    'shipping_methods' => 'Povolená doprava',
    'shipping_methods_help' => 'Pokud nevyberete nic, platba je dostupná u každé dopravy.',
    'all_shipping' => 'Všechny',
    'is_active' => 'Aktivní',
];
