<?php

namespace Modules\Orders\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\StockMovement;
use Modules\Catalog\StockService;
use Modules\Orders\CheckoutException;
use Modules\Orders\Events\OrderStatusChanged;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderHistoryEntry;
use Modules\Orders\OrderStatus;

/**
 * Zmena stavu objednávky. Pri stornovaní sa tovar vráti na sklad, pri obnovení stornovanej
 * objednávky sa znova odpíše.
 */
class ChangeOrderStatus
{
    public function __construct(private readonly StockService $stock) {}

    public function handle(Order $order, OrderStatus $status, ?User $user = null): void
    {
        $from = $order->status;

        if ($from === $status) {
            return;
        }

        DB::transaction(function () use ($order, $from, $status, $user): void {
            if ($status === OrderStatus::Cancelled) {
                $this->adjustStock($order, +1);
            } elseif ($from === OrderStatus::Cancelled) {
                $this->adjustStock($order, -1);
            }

            $order->update(['status' => $status]);
            $order->history()->create([
                'type' => OrderHistoryEntry::STATUS,
                'user_id' => $user?->id,
                'data' => ['from' => $from->value, 'to' => $status->value],
            ]);
        });

        OrderStatusChanged::dispatch($order, $from, $status);
    }

    private function adjustStock(Order $order, int $direction): void
    {
        foreach ($order->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            $product = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();

            if ($product === null || ! $product->track_stock) {
                continue;
            }

            if ($direction < 0 && $product->stock_quantity < $item->quantity) {
                throw new CheckoutException(__('orders::messages.not_enough_stock_to_restore', ['product' => $item->name]));
            }

            $this->stock->adjust(
                $product,
                $direction * $item->quantity,
                $direction > 0 ? StockMovement::CANCEL : StockMovement::RESTORE,
                $order->number,
            );
        }
    }
}
