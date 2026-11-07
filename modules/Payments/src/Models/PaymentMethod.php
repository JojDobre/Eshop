<?php

namespace Modules\Payments\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Payments\PaymentType;
use Modules\Shipping\Models\ShippingMethod;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property PaymentType $type
 * @property int $fee
 * @property string $currency
 * @property string|null $instructions
 * @property int $position
 * @property bool $is_active
 * @property-read Collection<int, ShippingMethod> $shippingMethods
 */
class PaymentMethod extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'description', 'instructions'];

    protected $attributes = [
        'fee' => 0,
        'position' => 0,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (PaymentMethod $method): void {
            $method->currency ??= app(CurrencyContext::class)->current();
        });
    }

    protected function casts(): array
    {
        return [
            'type' => PaymentType::class,
            'fee' => 'integer',
            'currency_fees' => 'array',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsToMany<ShippingMethod, $this>
     */
    public function shippingMethods(): BelongsToMany
    {
        return $this->belongsToMany(ShippingMethod::class);
    }

    /**
     * @param  Builder<PaymentMethod>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    /**
     * Dá sa platba použiť s daným spôsobom dopravy?
     */
    public function isAvailableFor(ShippingMethod $shipping): bool
    {
        return $this->shippingMethods->isEmpty() || $this->shippingMethods->contains('id', $shipping->id);
    }

    /**
     * Poplatok v mene: ručne zadaný, inak prepočítaný kurzom z meny platby.
     */
    public function feeIn(?string $currency = null): int
    {
        $currency ??= app(CurrencyContext::class)->current();
        $manual = $this->currency_fees[$currency] ?? null;

        if (is_numeric($manual)) {
            return (int) $manual;
        }

        return $this->fee === 0 ? 0 : app(Currencies::class)->convert($this->fee, $this->currency, $currency);
    }
}
