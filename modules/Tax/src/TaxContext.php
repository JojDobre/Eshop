<?php

namespace Modules\Tax;

/**
 * Ako sa zdaňuje konkrétna objednávka: tuzemsko, OSS (DPH krajiny doručenia)
 * alebo prenesenie daňovej povinnosti (bez DPH).
 */
final class TaxContext
{
    public const DOMESTIC = 'domestic';

    public const OSS = 'oss';

    public const REVERSE_CHARGE = 'reverse_charge';

    public function __construct(
        public readonly string $mode,
        public readonly string $country,
        public readonly string $homeCountry,
        public readonly bool $keepGrossPrices = true,
    ) {}

    public static function domestic(string $homeCountry): self
    {
        return new self(self::DOMESTIC, $homeCountry, $homeCountry);
    }

    public function isDomestic(): bool
    {
        return $this->mode === self::DOMESTIC;
    }

    public function isReverseCharge(): bool
    {
        return $this->mode === self::REVERSE_CHARGE;
    }
}
