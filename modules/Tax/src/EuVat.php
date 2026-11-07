<?php

namespace Modules\Tax;

/**
 * Členské štáty EÚ a predvyplnené sadzby DPH (základná a prvá znížená).
 *
 * Sadzby sa menia – po predvyplnení ich obchodník skontroluje (stav k 1. 1. 2026).
 */
final class EuVat
{
    /** @var array<string, array{standard: float, reduced: float}> */
    public const RATES = [
        'AT' => ['standard' => 20, 'reduced' => 10],
        'BE' => ['standard' => 21, 'reduced' => 6],
        'BG' => ['standard' => 20, 'reduced' => 9],
        'HR' => ['standard' => 25, 'reduced' => 13],
        'CY' => ['standard' => 19, 'reduced' => 9],
        'CZ' => ['standard' => 21, 'reduced' => 12],
        'DK' => ['standard' => 25, 'reduced' => 25],
        'EE' => ['standard' => 24, 'reduced' => 9],
        'FI' => ['standard' => 25.5, 'reduced' => 14],
        'FR' => ['standard' => 20, 'reduced' => 5.5],
        'DE' => ['standard' => 19, 'reduced' => 7],
        'GR' => ['standard' => 24, 'reduced' => 13],
        'HU' => ['standard' => 27, 'reduced' => 18],
        'IE' => ['standard' => 23, 'reduced' => 13.5],
        'IT' => ['standard' => 22, 'reduced' => 10],
        'LV' => ['standard' => 21, 'reduced' => 12],
        'LT' => ['standard' => 21, 'reduced' => 9],
        'LU' => ['standard' => 17, 'reduced' => 8],
        'MT' => ['standard' => 18, 'reduced' => 7],
        'NL' => ['standard' => 21, 'reduced' => 9],
        'PL' => ['standard' => 23, 'reduced' => 8],
        'PT' => ['standard' => 23, 'reduced' => 13],
        'RO' => ['standard' => 21, 'reduced' => 11],
        'SK' => ['standard' => 23, 'reduced' => 19],
        'SI' => ['standard' => 22, 'reduced' => 9.5],
        'ES' => ['standard' => 21, 'reduced' => 10],
        'SE' => ['standard' => 25, 'reduced' => 12],
    ];

    public static function isMember(?string $country): bool
    {
        return $country !== null && isset(self::RATES[strtoupper($country)]);
    }

    /**
     * Kód krajiny v IČ DPH (Grécko používa „EL“).
     */
    public static function vatPrefix(string $country): string
    {
        return $country === 'GR' ? 'EL' : $country;
    }
}
