<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class PrivacySettings extends Settings
{
    /** Nastavenie pravidiel uchovávania: kľúč => enabled, days. */
    public array $retention;

    public static function group(): string
    {
        return 'privacy';
    }
}
