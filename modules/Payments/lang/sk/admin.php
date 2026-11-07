<?php

return [
    'navigation' => 'Platby',
    'label' => 'Spôsob platby',
    'plural' => 'Spôsoby platby',
    'name' => 'Názov',
    'type' => 'Typ',
    'types' => [
        'bank_transfer' => 'Bankový prevod',
        'cash_on_delivery' => 'Dobierka',
        'cash' => 'Platba pri osobnom odbere',
        'card' => 'Platba kartou online',
    ],
    'description' => 'Popis',
    'description_help' => 'Zobrazí sa zákazníkovi pri výbere platby.',
    'instructions' => 'Pokyny po objednávke',
    'instructions_help' => 'Nepovinné. Zobrazia sa po odoslaní objednávky a v potvrdzujúcom e-maile.',
    'fee' => 'Poplatok',
    'position' => 'Poradie',
    'shipping_methods' => 'Povolená doprava',
    'shipping_methods_help' => 'Ak nevyberiete nič, platba je dostupná pri každej doprave.',
    'all_shipping' => 'Všetky',
    'is_active' => 'Aktívna',
];
