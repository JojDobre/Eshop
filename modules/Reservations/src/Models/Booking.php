<?php

namespace Modules\Reservations\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Modules\Catalog\Models\Product;
use Modules\Reservations\ReservationSettings;

/**
 * Rezervácia termínu služby alebo jedinečného kusu.
 *
 * @property int $id
 * @property string $number
 * @property string $token
 * @property int|null $booking_service_id
 * @property int|null $booking_resource_id
 * @property int|null $product_id
 * @property Carbon $starts_at
 * @property Carbon $ends_at
 * @property string $status pending, confirmed, cancelled, completed
 * @property string $payment_mode
 * @property bool $is_paid
 * @property int $amount_due
 * @property string $currency
 * @property string $customer_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $note
 */
class Booking extends Model
{
    public const PENDING = 'pending';

    public const CONFIRMED = 'confirmed';

    public const CANCELLED = 'cancelled';

    public const COMPLETED = 'completed';

    public const STATUSES = [self::PENDING, self::CONFIRMED, self::CANCELLED, self::COMPLETED];

    protected $guarded = [];

    protected function casts(): array
    {
        return ['starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_paid' => 'boolean', 'amount_due' => 'integer', 'reminded_at' => 'datetime', 'cancelled_at' => 'datetime'];
    }

    /**
     * Rezervácie, ktoré blokujú termín (nie zrušené).
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [self::PENDING, self::CONFIRMED, self::COMPLETED]);
    }

    /**
     * @return BelongsTo<BookingService, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(BookingService::class, 'booking_service_id');
    }

    /**
     * @return BelongsTo<BookingResource, $this>
     */
    public function resource(): BelongsTo
    {
        return $this->belongsTo(BookingResource::class, 'booking_resource_id');
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function title(): string
    {
        return $this->service?->name ?? $this->product?->name ?? $this->number;
    }

    public function url(): string
    {
        return route('reservations.show', $this->token);
    }

    /** Môže zákazník rezerváciu ešte zrušiť? */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::PENDING, self::CONFIRMED], true)
            && now()->addHours(app(ReservationSettings::class)->cancel_hours)->lte($this->starts_at);
    }

    public static function nextNumber(): string
    {
        $prefix = 'B'.now()->format('Y');
        $last = static::query()->where('number', 'like', $prefix.'%')->orderByDesc('number')->value('number');

        return $prefix.str_pad((string) ((int) substr((string) $last, strlen($prefix)) + 1), 5, '0', STR_PAD_LEFT);
    }
}
