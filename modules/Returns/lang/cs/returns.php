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
    'lookup_title' => 'Vrácení zboží a reklamace',
    'lookup_intro' => 'Zadejte číslo objednávky a e-mail, který jste u ní použili. Formulář funguje i bez zákaznického účtu.',
    'lookup_number' => 'Číslo objednávky',
    'lookup_email' => 'E-mail z objednávky',
    'lookup_submit' => 'Pokračovat',
    'lookup_not_found' => 'Objednávku s tímto číslem a e-mailem jsme nenašli.',
    'adr' => 'Pokud nejste spokojeni s vyřízením, můžete se obrátit na subjekt mimosoudního řešení sporů – v Česku Česká obchodní inšpekce (adr.coi.cz), na Slovensku Slovenská obchodná inšpekcia (www.soi.sk).',
];
