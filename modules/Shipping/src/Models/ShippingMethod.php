<?php

namespace Modules\Shipping\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Support\CurrencyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shipping\ShippingType;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property ShippingType $type
 * @property int $price
 * @property string $currency
 * @property int|null $free_from
 * @property string|null $pickup_details
 * @property int $position
 * @property bool $is_active
 */
class ShippingMethod extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'description', 'pickup_details'];

    protected $attributes = [
        'price' => 0,
        'position' => 0,
        'is_active' => true,
    ];

    protected static function booted(): void
    {
        static::creating(function (ShippingMethod $method): void {
            $method->currency ??= app(CurrencyContext::class)->current();
        });
    }

    protected function casts(): array
    {
        return [
            'type' => ShippingType::class,
            'price' => 'integer',
            'free_from' => 'integer',
            'position' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<ShippingMethod>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    /**
     * Cena dopravy pre objednávku s daným medzisúčtom (doprava zadarmo od určitej sumy).
     */
    public function priceFor(int $subtotal): int
    {
        if ($this->free_from !== null && $subtotal >= $this->free_from) {
            return 0;
        }

        return $this->price;
    }

    /**
     * Koľko ešte chýba do dopravy zadarmo (null = doprava zadarmo nie je).
     */
    public function remainingForFreeShipping(int $subtotal): ?int
    {
        if ($this->free_from === null || $this->price === 0) {
            return null;
        }

        return max(0, $this->free_from - $subtotal);
    }
}
