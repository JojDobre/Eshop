<?php

namespace Modules\Shipping\Carriers;

use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * Kuriér (Packeta, GLS, DPD, vlastný kuriér…). Každý kuriér je modul, ktorý toto rozhranie
 * implementuje a zaregistruje v CarrierRegistry. Pokladňa, objednávky a administrácia ho potom
 * používajú automaticky.
 */
interface ShippingCarrier
{
    /** Kľúč kuriéra, napr. „packeta“. */
    public function key(): string;

    public function label(): string;

    /** Sú vyplnené prístupové údaje? (bez nich sa dá kuriér vybrať, ale nevytvorí zásielku) */
    public function isConfigured(): bool;

    /** Podporuje kuriér daný typ dopravy (doručenie na adresu, výdajné miesto)? */
    public function supports(ShippingType $type): bool;

    /**
     * Výber výdajného miesta v pokladni:
     *  - ['widget' => 'nazov', 'config' => [...]] – JavaScript kuriéra (window.eshopPickupWidgets[nazov]),
     *  - ['list' => true] – vyhľadávanie v uloženom zozname (pickup_points),
     *  - null – kuriér výdajné miesta nemá.
     *
     * @return array<string, mixed>|null
     */
    public function pickupSelector(ShippingMethod $method): ?array;

    /**
     * Polia nastavení pre konkrétny spôsob dopravy (uložia sa do carrier_options).
     *
     * @return array<int, mixed>
     */
    public function methodFields(): array;

    /**
     * Vytvorí zásielku u kuriéra.
     *
     * @throws CarrierException
     */
    public function createShipment(ShipmentRequest $request): ShipmentResult;

    /**
     * Štítky zásielok v jednom PDF.
     *
     * @param  list<string>  $references  identifikátory zásielok u kuriéra (ShipmentResult::$reference)
     *
     * @throws CarrierException
     */
    public function labels(array $references): string;

    /**
     * Aktuálny stav zásielky (null = kuriér sledovanie nepodporuje).
     *
     * @throws CarrierException
     */
    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult;

    /**
     * Adresa sledovania zásielky pre zákazníka.
     *
     * @param  array<string, mixed>  $methodOptions
     */
    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string;

    /**
     * Overí prístupové údaje (napr. tlačidlo „Otestovať spojenie“).
     *
     * @throws CarrierException
     */
    public function testConnection(): void;

    /**
     * Stiahne zoznam výdajných miest do tabuľky pickup_points (0 = kuriér zoznam nepoužíva).
     *
     * @throws CarrierException
     */
    public function syncPickupPoints(): int;
}
