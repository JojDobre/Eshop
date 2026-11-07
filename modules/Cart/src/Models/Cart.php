<?php

namespace Modules\Cart\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $token
 * @property string $currency
 * @property string|null $coupon_code
 * @property-read Collection<int, CartItem> $items
 */
class Cart extends Model
{
    protected $guarded = [];

    /**
     * @return HasMany<CartItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('id');
    }
}
