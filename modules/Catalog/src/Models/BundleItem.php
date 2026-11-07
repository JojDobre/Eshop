<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Zložka balíčka (produkt alebo jeho variant v danom počte).
 *
 * @property int $id
 * @property int $bundle_id
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int $quantity
 */
class BundleItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['quantity' => 'integer', 'position' => 'integer'];
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

    public function name(): string
    {
        $name = $this->product?->name ?? '';

        return $this->variant ? $name.' – '.$this->variant->name() : $name;
    }
}
