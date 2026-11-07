<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Catalog\StockLots;

/**
 * Zásoba produktu (variantu) v jednom sklade a jednej šarži.
 *
 * @property int $id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int $warehouse_id
 * @property string|null $batch_number
 * @property Carbon|null $produced_at
 * @property Carbon|null $expires_at
 * @property int $quantity
 * @property-read Product $product
 * @property-read ProductVariant|null $variant
 * @property-read Warehouse $warehouse
 */
class StockLot extends Model
{
    protected $guarded = [];

    protected $attributes = ['quantity' => 0];

    protected static function booted(): void
    {
        // Načítané zásoby v pamäti sa po zmene zabudnú.
        static::saved(fn () => app(StockLots::class)->forget());
        static::deleted(fn () => app(StockLots::class)->forget());
    }

    protected function casts(): array
    {
        return ['produced_at' => 'date', 'expires_at' => 'date', 'quantity' => 'integer'];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(today());
    }

    /** Počet dní do expirácie (záporné = po expirácii, null = bez dátumu). */
    public function daysToExpiry(): ?int
    {
        return $this->expires_at === null ? null : (int) today()->diffInDays($this->expires_at, false);
    }

    public function productName(): string
    {
        $name = $this->product?->name ?? '';

        return $this->variant ? $name.' – '.$this->variant->name() : $name;
    }
}
