<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Carriers\ShipmentStatus;
use Modules\Shipping\Carriers\ShippingCarrier;

/**
 * Zásielka objednávky u kuriéra.
 *
 * @property int $id
 * @property int $order_id
 * @property string $carrier
 * @property string|null $carrier_reference
 * @property string|null $tracking_number
 * @property string|null $label_path
 * @property ShipmentStatus $status
 * @property string|null $status_text
 * @property int|null $cod_amount
 * @property string|null $cod_currency
 * @property Carbon|null $last_checked_at
 * @property Carbon|null $delivered_at
 * @property-read Order $order
 */
class Shipment extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'cod_amount' => 'integer',
            'last_checked_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function carrierService(): ?ShippingCarrier
    {
        return app(CarrierRegistry::class)->find($this->carrier);
    }

    /** Adresa sledovania zásielky pre zákazníka. */
    public function trackingUrl(): ?string
    {
        if (blank($this->tracking_number)) {
            return null;
        }

        $options = $this->order->shippingMethod?->carrier_options ?? [];

        return $this->carrierService()?->trackingUrl($this->tracking_number, $options);
    }
}
