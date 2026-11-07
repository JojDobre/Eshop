<?php

return [
    'welcome' => [
        'label' => 'Welcome (after sign-up)',
        'subject' => 'Welcome to {shop_name}',
        'body' => 'Hello {customer_name},

thank you for signing up. In your account you can see your orders, and your address will be filled in automatically next time.

[My account]({account_url})

Kind regards\\
{shop_name}',
    ],
    'password_reset' => [
        'label' => 'Password reset',
        'subject' => 'New password for {shop_name}',
        'body' => 'Hello {customer_name},

you asked for a new password. Set it using this link (valid for 60 minutes):

[Set a new password]({reset_url})

If you did not ask for a new password, ignore this e-mail.

Kind regards\\
{shop_name}',
    ],
];
