<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Ďalšie meny obchodu, kurzy a zaokrúhľovanie.
 */
class CurrencySettings extends Settings
{
    /**
     * Ďalšie meny okrem predvolenej (prázdne = obchod v jednej mene).
     *
     * @var list<string>
     */
    public array $enabled;

    /**
     * Kurzy: koľko jednotiek meny je 1 jednotka predvolenej meny (napr. EUR → CZK: 25.2).
     *
     * @var array<string, string>
     */
    public array $rates;

    /**
     * Zaokrúhľovanie prepočítaných cien: math, whole, ninety_nine.
     *
     * @var array<string, string>
     */
    public array $rounding;

    /** Kurzy sa každý deň aktualizujú z ECB. */
    public bool $auto_update;

    public ?string $rates_updated_at;

    public static function group(): string
    {
        return 'currencies';
    }
}
