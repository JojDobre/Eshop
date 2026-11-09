<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Základné nastavenia obchodu.
 */
class GeneralSettings extends Settings
{
    public string $shop_name;

    public ?string $tagline;

    public string $contact_email;

    public ?string $contact_phone;

    /** Predvolený jazyk obchodu (ISO 639-1, napr. „sk"). */
    public string $default_locale;

    /**
     * Ďalšie jazyky obchodu okrem predvoleného (prázdne = jednojazyčný obchod).
     *
     * @var list<string>
     */
    public array $locales;

    /** Hlavný vypínač ďalších jazykov (vypnutím sa výber jazykov zachová). */
    public bool $locales_enabled;

    /** Predvolená mena (ISO 4217, napr. „EUR"). */
    public string $default_currency;

    /** Krajina obchodu (ISO 3166-1 alpha-2, napr. „SK"). */
    public string $country;

    /** Názov aktívnej šablóny vzhľadu (adresár v themes/). */
    public string $theme;

    /** Skryté položky menu administrácie (cesty bez /admin/). */
    public array $admin_hidden_navigation = [];

    /** Veľkosť obchodu: small, medium, large alebo custom (vlastný výber funkcií). */
    public string $shop_size;

    /** Veľkosť z inštalátora čaká na použitie (moduly ešte neboli načítané). */
    public bool $shop_size_pending;

    /** Editor súborov šablóny v administrácii (iba hlavný administrátor). */
    public bool $theme_editor_enabled;

    public static function group(): string
    {
        return 'general';
    }
}
