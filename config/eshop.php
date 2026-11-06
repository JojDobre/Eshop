<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Adresáre
    |--------------------------------------------------------------------------
    |
    | modules  – interné moduly (každý podadresár so súborom module.json)
    | themes   – šablóny vzhľadu obchodu
    | state    – stav inštalácie a zapnutých modulov (musí byť zapisovateľný)
    |
    */

    'paths' => [
        'modules' => [base_path('modules')],
        'themes' => base_path('themes'),
        'state' => env('ESHOP_STATE_PATH', storage_path('app/eshop')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Záchranný režim
    |--------------------------------------------------------------------------
    |
    | Ak sa aplikácia kvôli modulu nespustí, nastavte ESHOP_SAFE_MODE=true v súbore .env.
    | Načítajú sa iba základné moduly a v administrácii sa dá problémový modul vypnúť.
    |
    */

    'safe_mode' => (bool) env('ESHOP_SAFE_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Predvolená šablóna vzhľadu
    |--------------------------------------------------------------------------
    */

    'default_theme' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Jazyky, meny a krajiny
    |--------------------------------------------------------------------------
    |
    | Zoznam ponúkaný v inštalátore a nastaveniach. Kódy jazykov podľa ISO 639-1
    | (čeština = „cs"), kódy mien podľa ISO 4217, krajiny podľa ISO 3166-1.
    |
    */

    'locales' => [
        'sk' => 'Slovenčina',
        'cs' => 'Čeština',
        'en' => 'English',
    ],

    'currencies' => [
        'EUR' => '€',
        'CZK' => 'Kč',
        'USD' => '$',
        'GBP' => '£',
    ],

    'countries' => ['SK', 'CZ', 'AT', 'DE', 'HU', 'PL', 'GB', 'US'],

];
