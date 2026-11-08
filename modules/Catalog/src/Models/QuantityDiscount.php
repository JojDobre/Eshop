<?php

namespace Modules\Catalog\Models;

use App\Models\CustomerGroup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Množstevná zľava: od min_quantity kusov zľava discount_percent % (customer_group_id null = pre všetkých).
 *
 * @property int $product_id
 * @property int|null $customer_group_id
 * @property int $min_quantity
 * @property float $discount_percent
 */
class QuantityDiscount extends Model
{
    protected $table = 'product_quantity_discounts';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['min_quantity' => 'integer', 'discount_percent' => 'float'];
    }

    /**
     * @return BelongsTo<CustomerGroup, $this>
     */
    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }
}
