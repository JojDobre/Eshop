<?php

namespace Modules\Accounting\Exporters;

/**
 * Sadzba DPH ako úroveň v účtovných programoch: základná, znížená, druhá znížená, bez DPH.
 * SK: 23 / 19 / 5 %, CZ: 21 / 12 %.
 */
final class VatRates
{
    public static function level(float $rate): string
    {
        return match (true) {
            $rate <= 0 => 'none',
            $rate >= 20 => 'high',
            $rate >= 10 => 'low',
            default => 'third',
        };
    }
}
