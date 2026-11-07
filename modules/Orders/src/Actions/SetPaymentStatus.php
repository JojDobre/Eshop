<?php

namespace Modules\Orders\Actions;

use App\Models\User;
use Modules\Orders\Events\OrderPaid;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderHistoryEntry;
use Modules\Orders\PaymentStatus;

class SetPaymentStatus
{
    public function handle(Order $order, PaymentStatus $status, ?User $user = null): void
    {
        if ($order->payment_status === $status) {
            return;
        }

        $order->update([
            'payment_status' => $status,
            'paid_at' => $status === PaymentStatus::Paid ? now() : null,
        ]);

        $order->history()->create([
            'type' => OrderHistoryEntry::PAYMENT,
            'user_id' => $user?->id,
            'data' => ['to' => $status->value],
        ]);

        if ($status === PaymentStatus::Paid) {
            OrderPaid::dispatch($order);
        }
    }
}
