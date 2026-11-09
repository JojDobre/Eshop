<?php

return [
    'submission' => [
        'label' => 'Nová zpráva z formuláře (pro obchod)',
        'subject' => 'Nová zpráva: {form_name}',
        'body' => "Z formuláře **{form_name}** přišla nová zpráva:\n\n{fields}\n\nVšechny zprávy najdete v administraci: {admin_url}\n\nNa zprávu můžete odpovědět přímo z e-mailu.",
    ],
];
