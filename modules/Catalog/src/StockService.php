<?php

namespace Modules\Catalog;

use App\Models\User;
use App\Settings\CatalogSettings;
use Modules\Catalog\Events\StockLow;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\StockMovement;
use Throwable;

/**
 * Zmeny skladu. Každá zmena sa zapíše do histórie (stock_movements).
 *
 * Volajúci zodpovedá za transakciu a zámok riadku (napr. PlaceOrder používa lockForUpdate).
 */
class StockService
{
    /**
     * Zmení stav skladu produktu alebo jeho variantu (pri produktoch s variantmi sa sklad sleduje pri variantoch).
     */
    public function adjust(
        Product $product,
        int $quantity,
        string $type,
        ?string $reference = null,
        ?string $note = null,
        ?User $user = null,
        ?ProductVariant $variant = null,
    ): void {
        if ($quantity === 0 || ! $product->track_stock) {
            return;
        }

        if ($variant !== null) {
            $before = $variant->stock_quantity;
            ProductVariant::withoutStockLogging(fn () => $variant->increment('stock_quantity', $quantity));
            $this->record($product, $quantity, $type, $reference, $note, $user, $variant);

            if ($quantity < 0) {
                $this->notifyIfLow($product, $before, $variant->stock_quantity, $variant);
            }

            return;
        }

        $before = $product->stock_quantity;

        Product::withoutStockLogging(function () use ($product, $quantity): void {
            $product->increment('stock_quantity', $quantity);
        });

        $this->record($product, $quantity, $type, $reference, $note, $user);

        if ($quantity < 0) {
            $this->notifyIfLow($product, $before, $product->stock_quantity);
        }
    }

    public function record(Product $product, int $quantity, string $type, ?string $reference = null, ?string $note = null, ?User $user = null, ?ProductVariant $variant = null): void
    {
        StockMovement::query()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'quantity' => $quantity,
            'stock_after' => $variant !== null ? $variant->stock_quantity : $product->stock_quantity,
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

    private function notifyIfLow(Product $product, int $before, int $after, ?ProductVariant $variant = null): void
    {
        $threshold = $this->lowStockThreshold($product);

        // Upozornenie iba pri prekročení hranice, nie pri každej ďalšej objednávke.
        if ($before > $threshold && $after <= $threshold) {
            StockLow::dispatch($product, $variant);
        }
    }
}
