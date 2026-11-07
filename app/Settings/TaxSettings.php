<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * DPH obchodu.
 */
class TaxSettings extends Settings
{
    /** Je obchodník platiteľ DPH? Neplatiteľ DPH na dokladoch DPH neuvádza. */
    public bool $vat_payer;

    /** Zadávajú sa ceny v administrácii vrátane DPH (true) alebo bez DPH (false)? */
    public bool $prices_include_vat;

    /** Režim OSS: DPH krajiny doručenia pri predaji spotrebiteľom do iných krajín EÚ. */
    public bool $oss_enabled;

    /** Pri OSS ostane konečná cena rovnaká (mení sa iba rozpis DPH). */
    public bool $oss_keep_gross_prices;

    /** Prenesenie daňovej povinnosti pre firmy z iných krajín EÚ s overeným IČ DPH. */
    public bool $reverse_charge_enabled;

    public static function group(): string
    {
        return 'tax';
    }
}
