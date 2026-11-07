<?php

return [
    'types' => [
        'withdrawal' => 'Odstoupení od smlouvy',
        'complaint' => 'Reklamace',
    ],
    'statuses' => [
        'new' => 'Nová',
        'in_progress' => 'Řeší se',
        'accepted' => 'Uznaná',
        'rejected' => 'Zamítnutá',
        'completed' => 'Vyřízená',
    ],
    'resolutions' => [
        'repair' => 'Oprava',
        'replacement' => 'Výměna za nové zboží',
        'refund' => 'Vrácení peněz',
        'discount' => 'Sleva z ceny',
    ],
    'no_items' => 'Vyberte alespoň jeden kus zboží.',
    'submitted' => ':type č. :number jsme přijali. Potvrzení jsme poslali na :email.',
];
