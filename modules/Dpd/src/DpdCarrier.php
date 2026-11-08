<?php

namespace Modules\Dpd;

use Modules\Shipping\Carriers\ManualCarrier;
use Modules\Shipping\ShippingType;

/**
 * DPD: sledovacie číslo z portálu DPD a odkaz na sledovanie zásielky.
 *
 * Aktuálna dokumentácia API DPD (DPD Shipping) je dostupná iba zákazníkom po prihlásení,
 * preto modul zatiaľ nevytvára zásielky cez API – zásielku podáte v portáli DPD a do objednávky
 * zapíšete číslo zásielky. Rozhranie ShippingCarrier umožňuje API doplniť bez zmien inde.
 */
class DpdCarrier extends ManualCarrier
{
    public function key(): string
    {
        return 'dpd';
    }

    public function label(): string
    {
        return 'DPD';
    }

    public function supports(ShippingType $type): bool
    {
        return $type === ShippingType::Delivery;
    }

    public function methodFields(): array
    {
        return [];
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        $locale = ['sk' => 'sk_SK', 'cs' => 'cs_CZ'][app()->getLocale()] ?? 'en_US';

        return 'https://tracking.dpd.de/status/'.$locale.'/parcel/'.rawurlencode($trackingNumber);
    }
}
