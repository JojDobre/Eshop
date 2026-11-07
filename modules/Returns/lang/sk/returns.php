<?php

return [
    'types' => [
        'withdrawal' => 'Odstúpenie od zmluvy',
        'complaint' => 'Reklamácia',
    ],
    'statuses' => [
        'new' => 'Nová',
        'in_progress' => 'Rieši sa',
        'accepted' => 'Uznaná',
        'rejected' => 'Zamietnutá',
        'completed' => 'Vybavená',
    ],
    'resolutions' => [
        'repair' => 'Oprava',
        'replacement' => 'Výmena za nový tovar',
        'refund' => 'Vrátenie peňazí',
        'discount' => 'Zľava z ceny',
    ],
    'no_items' => 'Vyberte aspoň jeden kus tovaru.',
    'submitted' => ':type č. :number sme prijali. Potvrdenie sme poslali na :email.',
];
