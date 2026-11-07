<?php

namespace Modules\Tax\Actions;

use Modules\Tax\EuVat;
use Modules\Tax\Models\TaxRate;

/**
 * Predvyplní sadzby ostatných krajín EÚ: predvolenej sadzbe základné sadzby, ostatným sadzbám
 * prvé znížené sadzby. Ručne zadané sadzby sa neprepisujú.
 */
final class FillEuRates
{
    public static function run(): int
    {
        $count = 0;

        foreach (TaxRate::query()->get() as $rate) {
            if ((float) $rate->rate === 0.0) {
                continue;
            }

            $kind = $rate->is_default ? 'standard' : 'reduced';
            $rates = $rate->country_rates ?? [];

            foreach (EuVat::RATES as $country => $values) {
                if (! isset($rates[$country])) {
                    $rates[$country] = $values[$kind];
                    $count++;
                }
            }

            $rate->update(['country_rates' => $rates]);
        }

        return $count;
    }
}
