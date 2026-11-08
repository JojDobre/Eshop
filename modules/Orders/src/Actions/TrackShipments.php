<?php

namespace Modules\Orders\Actions;

use Modules\Orders\Models\Shipment;
use Modules\Orders\OrderStatus;
use Modules\Orders\Shipping\CarrierHealth;
use Modules\Shipping\Carriers\ShipmentStatus;
use Throwable;

/**
 * Pravidelná kontrola stavu zásielok (webový cron). Doručená objednávka sa sama označí ako vybavená.
 */
class TrackShipments
{
    /** Ako často sa jedna zásielka kontroluje (hodiny). */
    public const INTERVAL_HOURS = 3;

    /** Najviac zásielok na jedno spustenie (krátky beh na webhostingu). */
    public const BATCH = 40;

    public function __construct(private readonly CarrierHealth $health, private readonly ChangeOrderStatus $status) {}

    public function handle(): int
    {
        $shipments = Shipment::query()
            ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Returned->value, ShipmentStatus::Cancelled->value])
            ->whereNotNull('carrier_reference')
            ->where(fn ($query) => $query->whereNull('last_checked_at')->orWhere('last_checked_at', '<', now()->subHours(self::INTERVAL_HOURS)))
            ->with('order')
            ->orderBy('last_checked_at')
            ->limit(self::BATCH)
            ->get();

        $updated = 0;

        foreach ($shipments as $shipment) {
            if ($this->check($shipment)) {
                $updated++;
            }
        }

        return $updated;
    }

    public function check(Shipment $shipment): bool
    {
        $carrier = $shipment->carrierService();

        if ($carrier === null || ! $carrier->isConfigured() || ! $this->health->isAvailable($carrier->key())) {
            return false;
        }

        try {
            $result = $carrier->track((string) $shipment->carrier_reference, $shipment->tracking_number);
            $this->health->success($carrier->key());
        } catch (Throwable $e) {
            $this->health->failure($carrier->key(), $e);
            $shipment->update(['last_checked_at' => now()]);

            return false;
        }

        if ($result === null) {
            $shipment->update(['last_checked_at' => now()]);

            return false;
        }

        $changed = $result->status !== $shipment->status;
        $shipment->update([
            'status' => $result->status,
            'status_text' => $result->text ? mb_substr($result->text, 0, 255) : $shipment->status_text,
            'last_checked_at' => now(),
            'delivered_at' => $result->status === ShipmentStatus::Delivered ? ($shipment->delivered_at ?? now()) : $shipment->delivered_at,
        ]);

        $order = $shipment->order;

        // Všetky zásielky doručené: objednávka je vybavená.
        if ($result->status === ShipmentStatus::Delivered && $order->status === OrderStatus::Shipped
            && $order->shipments()->where('status', '!=', ShipmentStatus::Delivered->value)->doesntExist()) {
            $this->status->handle($order, OrderStatus::Completed);
        }

        return $changed;
    }
}
