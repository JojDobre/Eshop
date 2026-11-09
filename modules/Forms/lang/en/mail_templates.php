<?php

return [
    'submission' => [
        'label' => 'New form message (for the shop)',
        'subject' => 'New message: {form_name}',
        'body' => "A new message arrived from the **{form_name}** form:\n\n{fields}\n\nAll messages are in the administration: {admin_url}\n\nYou can reply directly to this e-mail.",
    ],
];
