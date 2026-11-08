<?php

namespace Modules\Orders\Listeners;

use Modules\Orders\Events\OrderStatusChanged;
use Modules\Orders\GiftVouchers;
use Modules\Orders\OrderStatus;

/**
 * Zrušená objednávka vráti čerpanú sumu späť na darčekovú poukážku.
 */
class RefundGiftVoucher
{
    public function __construct(private readonly GiftVouchers $vouchers) {}

    public function handle(OrderStatusChanged $event): void
    {
        if ($event->order->status === OrderStatus::Cancelled) {
            $this->vouchers->refundForOrder($event->order);
        }
    }
}
