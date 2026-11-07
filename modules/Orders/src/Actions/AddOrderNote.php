<?php

namespace Modules\Orders\Actions;

use App\Models\User;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderHistoryEntry;

/**
 * Interná poznámka k objednávke (zákazník ju nevidí).
 */
class AddOrderNote
{
    public function handle(Order $order, string $note, ?User $user = null): void
    {
        $order->history()->create([
            'type' => OrderHistoryEntry::NOTE,
            'user_id' => $user?->id,
            'data' => ['note' => trim($note)],
        ]);
    }
}
