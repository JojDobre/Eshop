<?php

namespace Modules\Shipping\Carriers;

/**
 * Údaje pre vytvorenie zásielky (nezávislé od modulu objednávok).
 */
final class ShipmentRequest
{
    /**
     * @param  array{code: string, name?: string, carrier_code?: string|null}|null  $pickupPoint
     * @param  array<string, mixed>  $methodOptions  nastavenia spôsobu dopravy pre kuriéra
     */
    public function __construct(
        public readonly string $reference,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $company,
        public readonly string $email,
        public readonly string $phone,
        public readonly ?string $street,
        public readonly ?string $city,
        public readonly ?string $zip,
        public readonly string $country,
        public readonly ?array $pickupPoint,
        public readonly ?int $codAmount,
        public readonly string $currency,
        public readonly int $value,
        public readonly int $weightGrams,
        public readonly array $methodOptions = [],
        public readonly ?string $note = null,
        public readonly ?string $trackingNumber = null,
    ) {}

    /** Ulica bez čísla domu a číslo domu zvlášť (niektorí kuriéri ich chcú oddelene). */
    public function streetParts(): array
    {
        $street = trim((string) $this->street);

        if (preg_match('/^(.*?)[\s,]+(\d[\w\/\-]*)$/u', $street, $match)) {
            return [trim($match[1]), $match[2]];
        }

        return [$street, ''];
    }

    public function fullName(): string
    {
        return trim($this->firstName.' '.$this->lastName);
    }
}
