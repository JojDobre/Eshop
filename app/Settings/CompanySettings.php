<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Údaje o predávajúcom. Zobrazujú sa na webe a neskôr aj na dokladoch.
 */
class CompanySettings extends Settings
{
    public ?string $company_name;

    public ?string $street;

    public ?string $city;

    public ?string $zip;

    public ?string $country;

    /** IČO */
    public ?string $company_id;

    /** DIČ */
    public ?string $tax_id;

    /** IČ DPH */
    public ?string $vat_id;

    public ?string $iban;

    public ?string $swift;

    /** Zápis v registri, napr. „Zapísaná v OR OS Bratislava I, oddiel Sro, vložka č. …". */
    public ?string $registration;

    public static function group(): string
    {
        return 'company';
    }

    public function hasAddress(): bool
    {
        return filled($this->street) || filled($this->city);
    }
}
