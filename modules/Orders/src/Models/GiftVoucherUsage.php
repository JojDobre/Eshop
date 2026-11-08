<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pohyb na poukážke: čerpanie v objednávke (kladná suma) alebo vrátenie (záporná suma).
 *
 * @property int $gift_voucher_id
 * @property int|null $order_id
 * @property int $amount
 */
class GiftVoucherUsage extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
