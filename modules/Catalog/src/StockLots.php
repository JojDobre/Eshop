<?php

namespace Modules\Catalog;

use App\Models\User;
use App\Settings\CatalogSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\Models\StockLot;
use Modules\Catalog\Models\StockMovement;
use Modules\Catalog\Models\Warehouse;
use Throwable;

/**
 * Zásoby po skladoch a šaržiach (iba so zapnutými funkciami „Viac skladov“ alebo „Šarže“).
 *
 * Súčet zásob produktu vždy zodpovedá celkovému stavu v products.stock_quantity
 * (pri variantoch product_variants.stock_quantity). Výdaj ide v poradí skladov a v rámci
 * skladu podľa dátumu spotreby – najskôr tovar, ktorý expiruje najskôr (FEFO).
 */
class StockLots
{
    /** @var array<string, Collection<int, StockLot>> */
    private array $cache = [];

    public function enabled(): bool
    {
        return CatalogFeatures::enabled('warehouses') || CatalogFeatures::enabled('batches');
    }

    public function expirationEnabled(): bool
    {
        return CatalogFeatures::enabled('expiration');
    }

    /** Zabudne načítané zásoby (volá sa po každej zmene). */
    public function forget(): void
    {
        $this->cache = [];
    }

    /**
     * Zásoby produktu (variantu) v poradí výdaja.
     *
     * @return Collection<int, StockLot>
     */
    public function lotsFor(Product $product, ?ProductVariant $variant, bool $lock = false): Collection
    {
        $key = $product->id.':'.($variant?->id ?? 0);

        if (! $lock && isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        $lots = StockLot::query()
            ->where('product_id', $product->id)
            ->when($variant, fn ($query) => $query->where('product_variant_id', $variant->id), fn ($query) => $query->whereNull('product_variant_id'))
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->with('warehouse')
            ->get()
            ->sort(fn (StockLot $a, StockLot $b) => [$a->warehouse->position, $a->warehouse_id, $a->expires_at?->getTimestamp() ?? PHP_INT_MAX, $a->id]
                <=> [$b->warehouse->position, $b->warehouse_id, $b->expires_at?->getTimestamp() ?? PHP_INT_MAX, $b->id])
            ->values();

        return $lock ? $lots : $this->cache[$key] = $lots;
    }

    /**
     * Dá sa z tejto zásoby predávať? (aktívny sklad, pri sledovaní expirácie nie je po dátume
     * ani v lehote skrytia pred expiráciou)
     */
    public function isSellable(StockLot $lot): bool
    {
        if (! $lot->warehouse->is_active) {
            return false;
        }

        if (! $this->expirationEnabled() || $lot->expires_at === null) {
            return true;
        }

        return $lot->daysToExpiry() >= $this->setting('expiration_hide_days', 0);
    }

    /** Koľko kusov sa nedá predať (neaktívny sklad, expirované, tesne pred expiráciou). */
    public function blockedQuantity(Product $product, ?ProductVariant $variant): int
    {
        if (! $this->enabled()) {
            return 0;
        }

        return (int) $this->lotsFor($product, $variant)
            ->filter(fn (StockLot $lot) => $lot->quantity > 0 && ! $this->isSellable($lot))
            ->sum('quantity');
    }

    /** Najbližší dátum spotreby tovaru, ktorý sa predá ako prvý. */
    public function nextExpiry(Product $product, ?ProductVariant $variant): ?Carbon
    {
        if (! CatalogFeatures::enabled('batches')) {
            return null;
        }

        $lot = $this->lotsFor($product, $variant)
            ->filter(fn (StockLot $lot) => $lot->quantity > 0 && $this->isSellable($lot))
            ->first();

        return $lot?->expires_at;
    }

    /** Zľava (%) na tovar s blížiacou sa expiráciou (0 = bez zľavy). */
    public function expiryDiscountPercent(Product $product, ?ProductVariant $variant): int
    {
        if (! $this->expirationEnabled() || ! $product->track_stock) {
            return 0;
        }

        $percent = $this->setting('expiration_discount_percent', 0);
        $expiry = $percent > 0 ? $this->nextExpiry($product, $variant) : null;

        if ($expiry === null || (int) today()->diffInDays($expiry, false) > $this->setting('expiration_warning_days', 0)) {
            return 0;
        }

        return min(100, $percent);
    }

    /**
     * Dorovná rozpis zásob s celkovým stavom (napr. po zapnutí funkcie alebo ručnej úprave čísla).
     * Chýbajúce kusy pribudnú do predvoleného skladu bez šarže, prebytočné sa uberú v poradí výdaja.
     */
    public function reconcile(Product $product, ?ProductVariant $variant = null): void
    {
        if (! $this->enabled() || ! $product->track_stock) {
            return;
        }

        $total = (int) ($variant
            ? ProductVariant::query()->whereKey($variant->id)->value('stock_quantity')
            : Product::query()->whereKey($product->id)->value('stock_quantity'));
        $lots = $this->lotsFor($product, $variant, lock: true);
        $difference = $total - (int) $lots->sum('quantity');

        if ($difference > 0) {
            $default = Warehouse::default();
            $lot = $lots->first(fn (StockLot $lot) => $lot->warehouse_id === $default->id && $lot->batch_number === null && $lot->expires_at === null)
                ?? StockLot::query()->create(['product_id' => $product->id, 'product_variant_id' => $variant?->id, 'warehouse_id' => $default->id]);
            $lot->increment('quantity', $difference);
        }

        foreach ($lots as $lot) {
            if ($difference >= 0) {
                break;
            }

            $remove = min($lot->quantity, -$difference);

            if ($remove > 0) {
                $lot->decrement('quantity', $remove);
                $difference += $remove;
            }
        }

        $this->forget();
    }

    /**
     * Zmení zásobu a celkový stav naraz (so zápisom do histórie).
     */
    public function adjustLot(StockLot $lot, int $quantity, string $type, ?string $reference = null, ?string $note = null, ?User $user = null): void
    {
        if ($quantity === 0) {
            return;
        }

        $lot->increment('quantity', $quantity);
        $this->forget();

        $product = $lot->product;
        $variant = $lot->product_variant_id ? $lot->variant?->setRelation('product', $product) : null;

        app(StockService::class)->adjust($product, $quantity, $type, $reference, $note, $user, $variant, $lot);
    }

    /**
     * Vydá tovar zo zásob v poradí výdaja (volať v transakcii).
     *
     * @return list<array{product_id: int, variant_id: int|null, quantity: int, lot_id: int, warehouse_id: int, batch_number: string|null, expires_at: string|null}>
     *
     * @throws InsufficientStock
     */
    public function take(Product $product, ?ProductVariant $variant, int $quantity, string $type, ?string $reference = null): array
    {
        $this->reconcile($product, $variant);

        $lots = $this->lotsFor($product, $variant, lock: true)->filter(fn (StockLot $lot) => $lot->quantity > 0 && $this->isSellable($lot));

        if ((int) $lots->sum('quantity') < $quantity) {
            throw new InsufficientStock($variant ? $product->name.' – '.$variant->name() : $product->name);
        }

        $allocations = [];
        $remaining = $quantity;

        foreach ($lots as $lot) {
            $take = min($lot->quantity, $remaining);
            $this->adjustLot($lot->setRelation('product', $product), -$take, $type, $reference);
            $allocations[] = [
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $take,
                'lot_id' => $lot->id,
                'warehouse_id' => $lot->warehouse_id,
                'batch_number' => $lot->batch_number,
                'expires_at' => $lot->expires_at?->toDateString(),
            ];

            if (($remaining -= $take) === 0) {
                break;
            }
        }

        return $allocations;
    }

    /**
     * Príjem tovaru do skladu (prípadne do šarže s dátumom spotreby).
     */
    public function receive(
        Product $product,
        ?ProductVariant $variant,
        Warehouse $warehouse,
        int $quantity,
        ?string $batchNumber = null,
        ?string $producedAt = null,
        ?string $expiresAt = null,
        ?string $note = null,
        ?User $user = null,
    ): StockLot {
        $this->reconcile($product, $variant);

        $lot = StockLot::query()->firstOrCreate([
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'warehouse_id' => $warehouse->id,
            'batch_number' => filled($batchNumber) ? $batchNumber : null,
            'produced_at' => $producedAt ?: null,
            'expires_at' => $expiresAt ?: null,
        ]);

        $this->adjustLot($lot->setRelation('product', $product), $quantity, StockMovement::RECEIPT, $batchNumber, $note, $user);

        return $lot;
    }

    /**
     * Presun tovaru do iného skladu (šarža a dátumy ostávajú).
     */
    public function transfer(StockLot $lot, Warehouse $target, int $quantity, ?User $user = null): void
    {
        $quantity = min($quantity, $lot->quantity);

        if ($quantity <= 0 || $target->id === $lot->warehouse_id) {
            return;
        }

        $destination = StockLot::query()->firstOrCreate([
            'product_id' => $lot->product_id,
            'product_variant_id' => $lot->product_variant_id,
            'warehouse_id' => $target->id,
            'batch_number' => $lot->batch_number,
            'produced_at' => $lot->produced_at?->toDateString(),
            'expires_at' => $lot->expires_at?->toDateString(),
        ]);

        $service = app(StockService::class);
        $product = $lot->product;
        $variant = $lot->product_variant_id ? $lot->variant : null;

        $lot->decrement('quantity', $quantity);
        $destination->increment('quantity', $quantity);
        $this->forget();

        $service->record($product, -$quantity, StockMovement::TRANSFER, $target->name, user: $user, variant: $variant, lot: $lot);
        $service->record($product, $quantity, StockMovement::TRANSFER, $lot->warehouse->name, user: $user, variant: $variant, lot: $destination);
    }

    /**
     * Odpis tovaru (expirovaný, poškodený). Bez počtu sa odpíše celá zásoba.
     */
    public function writeOff(StockLot $lot, ?int $quantity = null, ?string $note = null, ?User $user = null): void
    {
        $quantity = min($quantity ?? $lot->quantity, $lot->quantity);

        if ($quantity > 0) {
            $this->adjustLot($lot, -$quantity, StockMovement::WRITE_OFF, $lot->batch_number, $note, $user);
        }
    }

    private function setting(string $key, int $default): int
    {
        try {
            return (int) app(CatalogSettings::class)->{$key};
        } catch (Throwable) {
            return $default;
        }
    }
}
