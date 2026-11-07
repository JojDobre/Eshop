<?php

namespace Modules\Tax;

use App\Settings\TaxSettings;

/**
 * Prepočty DPH. Všetky sumy sú celé čísla v najmenších jednotkách meny.
 *
 * Interne sa pracuje s cenami s DPH (to platí zákazník). Ak obchodník zadáva ceny bez DPH,
 * prepočítajú sa na ceny s DPH podľa sadzby produktu.
 */
class TaxCalculator
{
    public function __construct(private readonly TaxSettings $settings) {}

    public function isVatPayer(): bool
    {
        return $this->settings->vat_payer;
    }

    /**
     * Účinná sadzba v percentách (neplatiteľ DPH = 0).
     */
    public function effectiveRate(?float $rate): float
    {
        return $this->isVatPayer() ? (float) ($rate ?? 0) : 0.0;
    }

    /**
     * Cena s DPH z ceny zadanej v administrácii (podľa nastavenia s DPH alebo bez DPH).
     */
    public function grossFromEntered(int $amount, ?float $rate): int
    {
        if (! $this->isVatPayer() || $this->settings->prices_include_vat) {
            return $amount;
        }

        return (int) round($amount * (1 + $this->effectiveRate($rate) / 100));
    }

    /**
     * Základ dane (bez DPH) zo sumy s DPH.
     */
    public static function netFromGross(int $gross, float $rate): int
    {
        return $rate <= 0 ? $gross : (int) round($gross / (1 + $rate / 100));
    }

    /**
     * Rozpis DPH podľa sadzieb zo súm s DPH: [sadzba => ['net' => …, 'vat' => …, 'gross' => …]].
     *
     * @param  iterable<array{gross: int, rate: float}>  $lines
     * @return array<string, array{rate: float, net: int, vat: int, gross: int}>
     */
    public static function breakdown(iterable $lines): array
    {
        $groups = [];

        foreach ($lines as $line) {
            $key = number_format($line['rate'], 2, '.', '');
            $groups[$key] ??= ['rate' => $line['rate'], 'gross' => 0];
            $groups[$key]['gross'] += $line['gross'];
        }

        krsort($groups, SORT_NUMERIC);

        // DPH sa počíta zo súčtu za sadzbu (nie po riadkoch), aby nevznikali rozdiely zo zaokrúhlenia.
        return array_map(function (array $group): array {
            $net = self::netFromGross($group['gross'], $group['rate']);

            return ['rate' => $group['rate'], 'net' => $net, 'vat' => $group['gross'] - $net, 'gross' => $group['gross']];
        }, $groups);
    }
}
