<?php

return [
    'title' => 'Stripe (platba kartou)',
    'section_keys' => 'API klíče',
    'section_keys_help' => 'Klíče najdete ve Stripe Dashboard → Developers → API keys. Pro zkoušení použijte testovací klíče (sk_test_…).',
    'enabled' => 'Zapnout platbu kartou přes Stripe',
    'enabled_help' => 'Poté zapněte způsob platby „Platba kartou“ v Nastavení → Platby.',
    'publishable_key' => 'Publikovatelný klíč',
    'secret_key' => 'Tajný klíč',
    'secret_key_help' => 'Uloží se šifrovaně. Doporučujeme omezený klíč (rk_…) s oprávněním na Checkout Sessions.',
    'section_webhook' => 'Webhook (doporučeno)',
    'webhook_help' => 'Ve Stripe Dashboard → Developers → Webhooks přidejte adresu :url a události níže. Platba se potvrdí i tehdy, když se zákazník po zaplacení nevrátí do obchodu.',
    'webhook_secret' => 'Podpisový klíč webhooku',
    'verify' => 'Ověřit připojení',
    'verify_missing' => 'Nejprve zadejte a uložte tajný klíč.',
    'verify_failed' => 'Připojení ke Stripe selhalo',
    'verify_ok' => 'Připojení funguje (:mode).',
    'mode_test' => 'testovací režim',
    'mode_live' => 'ostrý režim',
];
