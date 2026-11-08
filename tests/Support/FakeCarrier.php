<?php

namespace Tests\Support;

use Modules\Shipping\Carriers\CarrierException;
use Modules\Shipping\Carriers\ShipmentRequest;
use Modules\Shipping\Carriers\ShipmentResult;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Carriers\ShippingCarrier;
use Modules\Shipping\Carriers\TrackingResult;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

/**
 * Testovací kuriér: zapamätá si požiadavky a vracia nastavené odpovede.
 */
class FakeCarrier implements ShippingCarrier
{
    /** @var list<ShipmentRequest> */
    public array $requests = [];

    public ShipmentStatus $status = ShipmentStatus::InTransit;

    public bool $failing = false;

    public function __construct(private readonly string $key = 'fake', private readonly bool $widget = false) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return 'Fake kuriér';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function supports(ShippingType $type): bool
    {
        return true;
    }

    public function pickupSelector(ShippingMethod $method): ?array
    {
        return $this->widget ? ['widget' => 'fake', 'config' => ['apiKey' => 'x'], 'adapter' => 'window.eshopPickupWidgets.fake = () => {};'] : ['list' => true];
    }

    public function methodFields(): array
    {
        return [];
    }

    public function createShipment(ShipmentRequest $request): ShipmentResult
    {
        if ($this->failing) {
            throw new CarrierException('API neodpovedá', CarrierException::UNAVAILABLE);
        }

        $this->requests[] = $request;
        $id = (string) (1000 + count($this->requests));

        return new ShipmentResult($id, 'TRK'.$id, '%PDF-label-'.$id);
    }

    public function labels(array $references): string
    {
        return '%PDF-'.implode(',', $references);
    }

    public function track(string $reference, ?string $trackingNumber = null): ?TrackingResult
    {
        if ($this->failing) {
            throw new CarrierException('API neodpovedá', CarrierException::UNAVAILABLE);
        }

        return new TrackingResult($this->status, 'Stav '.$this->status->value);
    }

    public function trackingUrl(string $trackingNumber, array $methodOptions = []): ?string
    {
        return 'https://tracking.example.com/'.$trackingNumber;
    }

    public function testConnection(): void {}

    public function syncPickupPoints(): int
    {
        return 0;
    }
}
