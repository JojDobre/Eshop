<?php

namespace App\Core\Support;

use Brick\Math\RoundingMode;
use Brick\Money\Money;
use NumberFormatter;

/**
 * Práca s cenami. Ceny sa ukladajú ako celé čísla v najmenších jednotkách meny
 * (centy, haliere), aby nevznikali chyby pri zaokrúhľovaní desatinných čísel.
 */
final class Price
{
    /**
     * Prevedie sumu zadanú človekom (napr. „12,90" alebo 12.9) na najmenšie jednotky meny.
     */
    public static function toMinor(string|int|float $amount, string $currency): int
    {
        $normalized = is_string($amount)
            ? str_replace([' ', "\u{00A0}", ','], ['', '', '.'], trim($amount))
            : (is_float($amount) ? (string) $amount : $amount);

        return Money::of($normalized === '' ? 0 : $normalized, $currency, roundingMode: RoundingMode::HalfUp)->getMinorAmount()->toInt();
    }

    /**
     * Prevedie najmenšie jednotky meny na desatinné číslo (napr. 1290 → „12.90") pre formuláre.
     */
    public static function toDecimal(int $minor, string $currency): string
    {
        return (string) Money::ofMinor($minor, $currency)->getAmount();
    }

    /**
     * Naformátuje cenu podľa jazyka, napr. „12,90 €" (sk) alebo „€12.90" (en).
     */
    public static function format(int $minor, string $currency, ?string $locale = null): string
    {
        $formatter = new NumberFormatter($locale ?? app()->getLocale(), NumberFormatter::CURRENCY);

        return (string) $formatter->formatCurrency((float) self::toDecimal($minor, $currency), $currency);
    }
}
