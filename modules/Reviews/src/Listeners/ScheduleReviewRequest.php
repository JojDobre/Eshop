<?php

namespace Modules\Reviews\Listeners;

use Modules\Orders\Events\OrderStatusChanged;
use Modules\Orders\OrderStatus;
use Modules\Reviews\ReviewSettings;

/**
 * Dokončená objednávka: naplánuje žiadosť o hodnotenie o nastavený počet dní.
 */
class ScheduleReviewRequest
{
    public function __construct(private readonly ReviewSettings $settings) {}

    public function handle(OrderStatusChanged $event): void
    {
        $order = $event->order;

        if ($event->to !== OrderStatus::Completed || $order->review_requested_at !== null || ! ReviewSettings::isEnabled() || ! $this->settings->request_enabled) {
            return;
        }

        $order->forceFill(['review_request_due_at' => now()->addDays(max(0, $this->settings->request_delay_days))])->save();
    }
}
