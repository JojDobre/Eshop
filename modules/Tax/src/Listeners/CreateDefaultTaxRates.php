<?php

namespace Modules\Tax\Listeners;

use App\Core\Events\EshopInstalled;
use Modules\Tax\Models\TaxRate;

/**
 * Sadzby DPH podľa krajiny obchodu. Sú iba predvolené: obchodník ich musí skontrolovať
 * a pri zmene zákona upraviť v administrácii.
 */
class CreateDefaultTaxRates
{
    /** @var array<string, list<array{0: array<string, string>, 1: string}>> */
    private const RATES = [
        'SK' => [
            [['sk' => 'Základná', 'cs' => 'Základní', 'en' => 'Standard'], '23'],
            [['sk' => 'Znížená', 'cs' => 'Snížená', 'en' => 'Reduced'], '19'],
            [['sk' => 'Super znížená', 'cs' => 'Super snížená', 'en' => 'Super reduced'], '5'],
        ],
        'CZ' => [
            [['sk' => 'Základná', 'cs' => 'Základní', 'en' => 'Standard'], '21'],
            [['sk' => 'Znížená', 'cs' => 'Snížená', 'en' => 'Reduced'], '12'],
        ],
    ];

    public function handle(EshopInstalled $event): void
    {
        if (TaxRate::query()->exists()) {
            return;
        }

        $locale = in_array($event->locale, ['sk', 'cs', 'en'], true) ? $event->locale : 'en';
        $rates = self::RATES[$event->country] ?? [[['sk' => 'Základná', 'cs' => 'Základní', 'en' => 'Standard'], '20']];

        foreach ($rates as $position => [$name, $rate]) {
            TaxRate::query()->create([
                'name' => [$event->locale => $name[$locale]],
                'rate' => $rate,
                'is_default' => $position === 0,
                'position' => $position,
            ]);
        }
    }
}
