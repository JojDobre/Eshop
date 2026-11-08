<?php

namespace Modules\Orders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Darčeková poukážka so zostatkom (dá sa čerpať po častiach).
 *
 * @property int $id
 * @property string $code
 * @property int $initial_amount
 * @property int $balance
 * @property string $currency
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property int|null $order_id
 * @property string|null $recipient_email
 * @property string|null $note
 */
class GiftVoucher extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['initial_amount' => 'integer', 'balance' => 'integer', 'expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public static function findByCode(?string $code): ?self
    {
        $code = self::normalize($code);

        return $code === '' ? null : static::query()->where('code', $code)->first();
    }

    /** Kód bez medzier a pomlčiek, veľkými písmenami (zákazník ho môže opísať s medzerami). */
    public static function normalize(?string $code): string
    {
        return strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', (string) $code));
    }

    /** Nový jedinečný kód, napr. „GV7K2M9QX4HT“ (bez zameniteľných znakov). */
    public static function generateCode(): string
    {
        do {
            $code = 'GV'.collect(range(1, 10))->map(fn () => '23456789ABCDEFGHJKLMNPQRSTUVWXYZ'[random_int(0, 31)])->implode('');
        } while (static::query()->where('code', $code)->exists());

        return $code;
    }

    /** Kód na zobrazenie po štvoriciach: „GV7K-2M9Q-X4HT“. */
    public function formattedCode(): string
    {
        return implode('-', str_split($this->code, 4));
    }

    /**
     * Prečo sa poukážka nedá použiť (null = dá sa).
     */
    public function rejectionReason(string $currency): ?string
    {
        return match (true) {
            ! $this->is_active => __('orders::messages.voucher_invalid'),
            $this->expires_at !== null && $this->expires_at->isPast() => __('orders::messages.voucher_expired'),
            $this->balance <= 0 => __('orders::messages.voucher_used'),
            $this->currency !== $currency => __('orders::messages.voucher_currency', ['currency' => $this->currency]),
            default => null,
        };
    }

    /**
     * @return HasMany<GiftVoucherUsage, $this>
     */
    public function usages(): HasMany
    {
        return $this->hasMany(GiftVoucherUsage::class);
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public static function maskedCode(string $code): string
    {
        return Str::mask($code, '•', 2, max(0, strlen($code) - 6));
    }
}
