<?php

return [
    'title' => 'Stripe (card payments)',
    'section_keys' => 'API keys',
    'section_keys_help' => 'Find the keys in Stripe Dashboard → Developers → API keys. Use test keys (sk_test_…) to try it out.',
    'enabled' => 'Enable card payments via Stripe',
    'enabled_help' => 'Then activate the “Card payment” method in Settings → Payments.',
    'publishable_key' => 'Publishable key',
    'secret_key' => 'Secret key',
    'secret_key_help' => 'Stored encrypted. We recommend a restricted key (rk_…) with access to Checkout Sessions.',
    'section_webhook' => 'Webhook (recommended)',
    'webhook_help' => 'In Stripe Dashboard → Developers → Webhooks add the URL :url and the events below. The payment is then confirmed even if the customer does not return to the shop.',
    'webhook_secret' => 'Webhook signing secret',
    'verify' => 'Test connection',
    'verify_missing' => 'Enter and save the secret key first.',
    'verify_failed' => 'Connection to Stripe failed',
    'verify_ok' => 'The connection works (:mode).',
    'mode_test' => 'test mode',
    'mode_live' => 'live mode',
];
