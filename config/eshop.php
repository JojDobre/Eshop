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

    // Editor súborov šablóny v administrácii. Hodnota false ho vypne úplne (nedá sa zapnúť ani v nastaveniach).
    'theme_editor' => (bool) env('ESHOP_THEME_EDITOR', true),

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

    /*
    | Jazyky, ktoré sa dajú pridať ako ďalšie jazyky obsahu obchodu. Texty rozhrania
    | (tlačidlá, e-maily) sú v slovenčine, češtine a angličtine; ostatné jazyky použijú
    | anglické texty rozhrania, kým sa nedoplnia preklady.
    */
    'content_locales' => [
        'sk' => 'Slovenčina',
        'cs' => 'Čeština',
        'en' => 'English',
        'de' => 'Deutsch',
        'pl' => 'Polski',
        'hu' => 'Magyar',
        'uk' => 'Українська',
        'it' => 'Italiano',
        'fr' => 'Français',
        'es' => 'Español',
        'ro' => 'Română',
        'hr' => 'Hrvatski',
        'sl' => 'Slovenščina',
    ],

    // Predpona v adrese, ak sa líši od kódu jazyka (napr. čeština „/cz/…").
    'locale_url_codes' => [
        'cs' => 'cz',
        'uk' => 'ua',
    ],

    'currencies' => [
        'EUR' => '€',
        'CZK' => 'Kč',
        'USD' => '$',
        'GBP' => '£',
        'PLN' => 'zł',
        'HUF' => 'Ft',
        'CHF' => 'CHF',
        'RON' => 'lei',
        'SEK' => 'kr',
        'DKK' => 'kr',
        'NOK' => 'kr',
    ],

    'countries' => ['SK', 'CZ', 'AT', 'DE', 'HU', 'PL', 'BE', 'BG', 'HR', 'CY', 'DK', 'EE', 'FI', 'FR', 'GR', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PT', 'RO', 'SI', 'ES', 'SE', 'GB', 'CH', 'NO', 'UA', 'US'],

    /*
    |--------------------------------------------------------------------------
    | Bezpečnosť
    |--------------------------------------------------------------------------
    |
    | hsts – na HTTPS posielať hlavičku Strict-Transport-Security (prehliadač potom
    |        obchod otvára iba cez HTTPS). Vypnite, ak HTTPS ešte nie je spoľahlivé.
    |
    */

    'security' => [
        'hsts' => (bool) env('ESHOP_HSTS', true),
    ],

];
