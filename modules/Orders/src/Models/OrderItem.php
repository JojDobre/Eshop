<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Models\Product;

/**
 * Položka objednávky. Názov a cena sú kópiou z času objednávky.
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property string $name
 * @property string|null $variant_name
 * @property string|null $sku
 * @property int $quantity
 * @property int $unit_price
 * @property int $total
 */
class OrderItem extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'addons' => 'array',
            'allocations' => 'array',
            'quantity' => 'integer',
            'unit_price' => 'integer',
            'total' => 'integer',
            'tax_rate' => 'float',
        ];
    }

    /**
     * Názov s variantom, napr. „Tričko – M / červená".
     */
    public function fullName(): string
    {
        $name = $this->variant_name ? $this->name.' – '.$this->variant_name : $this->name;

        // Doplnkové voľby: „Hrnček + Gravírovanie: Pre Janu, Darčekové balenie“.
        if (! empty($this->addons)) {
            $name .= ' + '.implode(', ', array_map(fn (array $addon) => filled($addon['text'] ?? null) ? $addon['name'].': '.$addon['text'] : $addon['name'], $this->addons));
        }

        return $name;
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
