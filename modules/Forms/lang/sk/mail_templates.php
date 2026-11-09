<?php

return [
    'submission' => [
        'label' => 'Nová správa z formulára (pre obchod)',
        'subject' => 'Nová správa: {form_name}',
        'body' => "Z formulára **{form_name}** prišla nová správa:\n\n{fields}\n\nVšetky správy nájdete v administrácii: {admin_url}\n\nNa správu môžete odpovedať priamo z e-mailu.",
    ],
];
