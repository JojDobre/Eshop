<?php

namespace Modules\Storefront\Cookies;

use Spatie\LaravelSettings\Settings;

class CookieSettings extends Settings
{
    /** Zobraziť lištu aj bez vložených kódov (napr. kvôli vlastným skriptom v šablóne). */
    public bool $enabled;

    /** Kód analytiky (napr. Google Analytics) – načíta sa až po súhlase. */
    public ?string $analytics_code;

    /** Marketingový kód (napr. Meta Pixel) – načíta sa až po súhlase. */
    public ?string $marketing_code;

    public static function group(): string
    {
        return 'cookies';
    }
}
