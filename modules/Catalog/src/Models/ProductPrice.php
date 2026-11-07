<?php

namespace Modules\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cena produktu v jednej mene (v najmenších jednotkách, s DPH).
 *
 * @property int $product_id
 * @property string $currency
 * @property int $amount
 */
class ProductPrice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
