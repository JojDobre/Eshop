<?php

namespace Modules\Reservations\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Support\Currencies;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Rezervovateľná služba (napr. strihanie, masáž, konzultácia).
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $duration_minutes
 * @property int $buffer_minutes
 * @property int $capacity
 * @property int $price
 * @property string $currency
 * @property string $payment_mode on_site, deposit, prepay
 * @property int $deposit
 * @property bool $is_active
 */
class BookingService extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'description'];

    protected $attributes = ['duration_minutes' => 60, 'buffer_minutes' => 0, 'capacity' => 1, 'price' => 0, 'payment_mode' => 'on_site', 'deposit' => 0, 'is_active' => true, 'position' => 0];

    protected function casts(): array
    {
        return ['duration_minutes' => 'integer', 'buffer_minutes' => 'integer', 'capacity' => 'integer', 'price' => 'integer', 'deposit' => 'integer', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::creating(fn (BookingService $service) => $service->currency ??= app(Currencies::class)->default());
    }

    /**
     * @param  Builder<BookingService>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsToMany<BookingResource, $this>
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(BookingResource::class, 'booking_resource_service');
    }

    /** Suma, ktorú treba zaplatiť vopred (záloha alebo celá cena). */
    public function amountDue(): int
    {
        return match ($this->payment_mode) {
            'prepay' => $this->price,
            'deposit' => min($this->deposit, $this->price ?: $this->deposit),
            default => 0,
        };
    }
}
