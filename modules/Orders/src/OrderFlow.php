<?php

namespace Modules\Orders;

use Modules\Orders\Models\Order;
use Modules\Shipping\ShippingType;

/**
 * Priebeh objednávky pre administráciu: kroky (nová → spracováva sa → pripravená/odoslaná → vybavená)
 * a ďalší krok, na ktorý sa dá objednávku posunúť jedným kliknutím.
 */
final class OrderFlow
{
    /**
     * Kroky podľa spôsobu doručenia (osobný odber = „pripravená na vyzdvihnutie“, inak „odoslaná“).
     *
     * @return list<OrderStatus>
     */
    public static function steps(Order $order): array
    {
        $handover = $order->shipping_type === ShippingType::Pickup || $order->status === OrderStatus::ReadyForPickup
            ? OrderStatus::ReadyForPickup
            : OrderStatus::Shipped;

        // Bez dopravy (digitálny tovar, služba) sa odovzdanie preskočí.
        if ($order->shipping_type === ShippingType::None && $order->status !== $handover) {
            return [OrderStatus::New, OrderStatus::Processing, OrderStatus::Completed];
        }

        return [OrderStatus::New, OrderStatus::Processing, $handover, OrderStatus::Completed];
    }

    public static function next(Order $order): ?OrderStatus
    {
        if ($order->status === OrderStatus::Cancelled) {
            return null;
        }

        $steps = self::steps($order);
        $index = array_search($order->status, $steps, true);

        return $index === false ? null : ($steps[$index + 1] ?? null);
    }
}
