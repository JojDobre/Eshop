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

    /** Predvolená mena (ISO 4217, napr. „EUR"). */
    public string $default_currency;

    /** Krajina obchodu (ISO 3166-1 alpha-2, napr. „SK"). */
    public string $country;

    /** Názov aktívnej šablóny vzhľadu (adresár v themes/). */
    public string $theme;

    public static function group(): string
    {
        return 'general';
    }
}
