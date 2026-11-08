<?php

namespace Modules\Shipping\Carriers;

use Filament\Forms\Components\TextInput;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * Vlastný kuriér bez napojenia: sledovacie číslo zadá obchodník, zákazník dostane odkaz na sledovanie
 * podľa vzoru adresy (napr. SPS, Slovenská pošta, vlastný rozvoz).
 */
class ManualCarrier implements ShippingCarrier
{
    public function key(): string
    {
        return 'manual';
    }

    public function label(): string
    {
        return __('shipping::admin.carriers.manual');
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function supports(ShippingType $type): bool
    {
        return in_array($type, [ShippingType::Delivery, ShippingType::Pickup], true);
    }

    public function pickupSelector(ShippingMethod $method): ?array
    {
        return null;
    }

    public function methodFields(): array
    {
        return [
            TextInput::make('tracking_url')->label(__('shipping::admin.carriers.tracking_url'))
                ->helperText(__('shipping::admin.carriers.tracking_url_help'))
                ->maxLength(255)->rule('regex:/^https?:\/\//'),
        ];
    }

    public function createShipment(ShipmentRequest $request): ShipmentResult
    {
        // Zásielku podá obchodník sám, do systému iba zapíše sledovacie číslo (môže byť aj prázdne).
        $number = trim((string) $request->trackingNumber);

        return new ShipmentResult($number !== '' ? $number : $request->reference, $number !== '' ? $number : null);
    }

    public function labels(array $references): string
    {
        throw new CarrierException(__('shipping::admin.carriers.no_labels'), CarrierException::CONFIGURATION);
    }

    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult
    {
        return null;
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        $template = (string) ($methodOptions['tracking_url'] ?? '');

        return $template !== '' ? str_replace('{number}', rawurlencode($trackingNumber), $template) : null;
    }

    public function testConnection(): void {}

    public function syncPickupPoints(): int
    {
        return 0;
    }
}
