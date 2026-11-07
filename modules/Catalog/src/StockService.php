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

    /**
     * Vydá tovar pre položku objednávky a vráti, odkiaľ sa vydal (na vrátenie a dohľadanie).
     * Balíček sa vydá zo svojich zložiek. Volať v transakcii.
     *
     * @return list<array{product_id: int, variant_id: int|null, quantity: int}>
     *
     * @throws InsufficientStock
     */
    public function take(Product $product, ?ProductVariant $variant, int $quantity, string $type, ?string $reference = null): array
    {
        if ($product->isBundle()) {
            $allocations = [];

            foreach ($product->bundleItems as $item) {
                $component = Product::query()->whereKey($item->product_id)->lockForUpdate()->first();
                $componentVariant = $item->product_variant_id
                    ? ProductVariant::query()->whereKey($item->product_variant_id)->lockForUpdate()->first()?->setRelation('product', $component)
                    : null;

                if ($component === null) {
                    continue;
                }

                $allocations = [...$allocations, ...$this->take($component, $componentVariant, $quantity * $item->quantity, $type, $reference)];
            }

            return $allocations;
        }

        $available = $variant ? $variant->isAvailable($quantity) : $product->isAvailable($quantity);

        if (! $available) {
            throw new InsufficientStock($variant ? $product->name.' – '.$variant->name() : $product->name);
        }

        $this->adjust($product, -$quantity, $type, $reference, variant: $variant);

        return [['product_id' => $product->id, 'variant_id' => $variant?->id, 'quantity' => $quantity]];
    }

    /**
     * Vráti tovar podľa záznamu z take() (napr. pri storne objednávky).
     *
     * @param  list<array{product_id: int, variant_id: int|null, quantity: int}>  $allocations
     */
    public function giveBack(array $allocations, string $type, ?string $reference = null): void
    {
        foreach ($allocations as $allocation) {
            $product = Product::query()->whereKey($allocation['product_id'])->lockForUpdate()->first();

            if ($product === null) {
                continue;
            }

            $variant = $allocation['variant_id'] ? ProductVariant::query()->whereKey($allocation['variant_id'])->lockForUpdate()->first() : null;
            $this->adjust($product, (int) $allocation['quantity'], $type, $reference, variant: $variant);
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
