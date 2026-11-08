<?php

namespace Modules\Catalog\Models;

use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cena produktu (alebo variantu) pre zákaznícku skupinu v jednej mene.
 *
 * @property int $product_id
 * @property int|null $product_variant_id
 * @property int $customer_group_id
 * @property string $currency
 * @property int $amount
 */
class ProductGroupPrice extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * @return BelongsTo<CustomerGroup, $this>
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }
}
