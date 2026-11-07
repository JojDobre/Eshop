<?php

namespace Modules\Catalog;

use App\Models\User;
use App\Settings\CatalogSettings;
use Modules\Catalog\Events\StockLow;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\StockMovement;
use Throwable;

/**
 * Zmeny skladu. Každá zmena sa zapíše do histórie (stock_movements).
 *
 * Volajúci zodpovedá za transakciu a zámok riadku (napr. PlaceOrder používa lockForUpdate).
 */
class StockService
{
    public function adjust(
        Product $product,
        int $quantity,
        string $type,
        ?string $reference = null,
        ?string $note = null,
        ?User $user = null,
    ): void {
        if ($quantity === 0 || ! $product->track_stock) {
            return;
        }

        $before = $product->stock_quantity;

        Product::withoutStockLogging(function () use ($product, $quantity): void {
            $product->increment('stock_quantity', $quantity);
        });

        $this->record($product, $quantity, $type, $reference, $note, $user);

        if ($quantity < 0) {
            $this->notifyIfLow($product, $before);
        }
    }

    public function record(Product $product, int $quantity, string $type, ?string $reference = null, ?string $note = null, ?User $user = null): void
    {
        StockMovement::query()->create([
            'product_id' => $product->id,
            'quantity' => $quantity,
            'stock_after' => $product->stock_quantity,
            'type' => $type,
            'reference' => $reference,
            'note' => $note,
            'user_id' => $user?->id ?? (auth()->user() instanceof User ? auth()->id() : null),
        ]);
    }

    public function lowStockThreshold(Product $product): int
    {
        if ($product->low_stock_threshold !== null) {
            return $product->low_stock_threshold;
        }

        try {
            return app(CatalogSettings::class)->low_stock_threshold;
        } catch (Throwable) {
            return 3;
        }
    }

    private function notifyIfLow(Product $product, int $before): void
    {
        $threshold = $this->lowStockThreshold($product);

        // Upozornenie iba pri prekročení hranice, nie pri každej ďalšej objednávke.
        if ($before > $threshold && $product->stock_quantity <= $threshold) {
            StockLow::dispatch($product);
        }
    }
}
