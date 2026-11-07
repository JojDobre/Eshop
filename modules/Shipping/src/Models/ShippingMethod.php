<?php

namespace Modules\Shipping\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Support\Currencies;
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
 * @property list<array{up_to: int, price: int}>|null $weight_rates
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
            'currency_prices' => 'array',
            'weight_rates' => 'array',
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
     * Cena dopravy pre objednávku (doprava zadarmo od určitej sumy, inak podľa hmotnosti, inak základná cena).
     *
     * @param  int  $weight  Hmotnosť objednávky v gramoch.
     */
    public function priceFor(int $subtotal, int $weight = 0, ?string $currency = null): int
    {
        $currency ??= app(CurrencyContext::class)->current();
        $freeFrom = $this->freeFromIn($currency);

        if ($freeFrom !== null && $subtotal >= $freeFrom) {
            return 0;
        }

        foreach ($this->sortedWeightRates() as $rate) {
            if ($weight <= $rate['up_to']) {
                return $this->convert($rate['price'], $currency);
            }
        }

        return $this->amountIn('price', $this->price, $currency);
    }

    /** Hranica dopravy zadarmo v mene (null = doprava zadarmo nie je). */
    public function freeFromIn(?string $currency = null): ?int
    {
        return $this->free_from === null ? null : $this->amountIn('free_from', $this->free_from, $currency ?? app(CurrencyContext::class)->current());
    }

    /**
     * Suma v mene: ručne zadaná, inak prepočítaná kurzom z meny dopravy.
     */
    private function amountIn(string $field, int $amount, string $currency): int
    {
        $manual = $this->currency_prices[$currency][$field] ?? null;

        return is_numeric($manual) ? (int) $manual : $this->convert($amount, $currency);
    }

    private function convert(int $amount, string $currency): int
    {
        return $amount === 0 ? 0 : app(Currencies::class)->convert($amount, $this->currency, $currency);
    }

    /**
     * Dá sa doprava použiť pre objednávku s danou hmotnosťou? (Ťažšie ako posledné pásmo = nie.)
     */
    public function isAvailableForWeight(int $weight): bool
    {
        $rates = $this->sortedWeightRates();

        return $rates === [] || $weight <= end($rates)['up_to'];
    }

    /**
     * @return list<array{up_to: int, price: int}>
     */
    public function sortedWeightRates(): array
    {
        $rates = array_values(array_filter(
            array_map(fn ($rate) => ['up_to' => (int) ($rate['up_to'] ?? 0), 'price' => (int) ($rate['price'] ?? 0)], $this->weight_rates ?? []),
            fn ($rate) => $rate['up_to'] > 0,
        ));

        usort($rates, fn ($a, $b) => $a['up_to'] <=> $b['up_to']);

        return $rates;
    }

    /**
     * Koľko ešte chýba do dopravy zadarmo (null = doprava zadarmo nie je).
     */
    public function remainingForFreeShipping(int $subtotal, ?string $currency = null): ?int
    {
        $freeFrom = $this->freeFromIn($currency);

        if ($freeFrom === null || $this->price === 0) {
            return null;
        }

        return max(0, $freeFrom - $subtotal);
    }
}
