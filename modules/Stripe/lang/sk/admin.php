<?php

return [
    'title' => 'Stripe (platba kartou)',
    'section_keys' => 'API kľúče',
    'section_keys_help' => 'Kľúče nájdete v Stripe Dashboard → Developers → API keys. Na skúšanie použite testovacie kľúče (sk_test_…).',
    'enabled' => 'Zapnúť platbu kartou cez Stripe',
    'enabled_help' => 'Potom zapnite spôsob platby „Platba kartou“ v Nastavenia → Platby.',
    'publishable_key' => 'Publikovateľný kľúč',
    'secret_key' => 'Tajný kľúč',
    'secret_key_help' => 'Uloží sa šifrovane. Odporúčame obmedzený kľúč (rk_…) s oprávnením na Checkout Sessions.',
    'section_webhook' => 'Webhook (odporúčané)',
    'webhook_help' => 'V Stripe Dashboard → Developers → Webhooks pridajte adresu :url a udalosti nižšie. Platba sa potvrdí aj vtedy, keď sa zákazník po zaplatení nevráti do obchodu.',
    'webhook_secret' => 'Podpisový kľúč webhooku',
    'verify' => 'Overiť pripojenie',
    'verify_missing' => 'Najprv zadajte a uložte tajný kľúč.',
    'verify_failed' => 'Pripojenie k Stripe zlyhalo',
    'verify_ok' => 'Pripojenie funguje (:mode).',
    'mode_test' => 'testovací režim',
    'mode_live' => 'ostrý režim',
];
