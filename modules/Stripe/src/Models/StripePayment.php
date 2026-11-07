<?php

namespace Modules\Stripe\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Orders\Models\Order;

/**
 * @property int $id
 * @property int $order_id
 * @property string $session_id
 * @property string|null $payment_intent
 * @property string $status
 * @property int $amount
 * @property string $currency
 */
class StripePayment extends Model
{
    public const OPEN = 'open';

    public const PAID = 'paid';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'amount' => 'integer'];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
