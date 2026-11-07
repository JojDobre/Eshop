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

    public static function group(): string
    {
        return 'tax';
    }
}
