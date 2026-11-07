<?php

namespace Modules\Orders\Models;

use App\Core\Support\CurrencyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Modules\Orders\CouponType;

/**
 * Zľavový kupón na celý nákup.
 *
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property CouponType $type
 * @property int $value Percentá alebo suma v najmenších jednotkách meny.
 * @property string $currency
 * @property int|null $min_order_amount
 * @property int|null $usage_limit
 * @property int $used_count
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property bool $is_active
 */
class Coupon extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'used_count' => 0,
        'is_active' => true,
        'value' => 0,
    ];

    protected function casts(): array
    {
        return [
            'type' => CouponType::class,
            'value' => 'integer',
            'min_order_amount' => 'integer',
            'usage_limit' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Coupon $coupon): void {
            $coupon->code = self::normalize($coupon->code);
            $coupon->currency ??= app(CurrencyContext::class)->current();
        });
    }

    public static function normalize(string $code): string
    {
        return mb_strtoupper(trim($code));
    }

    public static function findByCode(?string $code): ?self
    {
        return filled($code) ? static::query()->where('code', self::normalize((string) $code))->first() : null;
    }

    /**
     * Prečo sa kupón nedá použiť (preložená správa), alebo null, ak sa použiť dá.
     */
    public function rejectionReason(int $subtotal, string $currency): ?string
    {
        return match (true) {
            ! $this->is_active => __('orders::messages.coupon_invalid'),
            $this->starts_at !== null && $this->starts_at->isFuture() => __('orders::messages.coupon_invalid'),
            $this->ends_at !== null && $this->ends_at->isPast() => __('orders::messages.coupon_expired'),
            $this->usage_limit !== null && $this->used_count >= $this->usage_limit => __('orders::messages.coupon_used_up'),
            $this->type !== CouponType::Percent && $this->currency !== $currency => __('orders::messages.coupon_invalid'),
            $this->min_order_amount !== null && $subtotal < $this->min_order_amount => __('orders::messages.coupon_min_order', [
                'amount' => format_price($this->min_order_amount, $this->currency),
            ]),
            default => null,
        };
    }

    /**
     * Zľava z tovaru (nikdy nie viac ako hodnota tovaru).
     */
    public function discountFor(int $subtotal): int
    {
        $discount = match ($this->type) {
            CouponType::Percent => (int) round($subtotal * min(100, $this->value) / 100),
            CouponType::Fixed => $this->value,
            CouponType::FreeShipping => 0,
        };

        return min($subtotal, $discount);
    }

    public function givesFreeShipping(): bool
    {
        return $this->type === CouponType::FreeShipping;
    }
}
