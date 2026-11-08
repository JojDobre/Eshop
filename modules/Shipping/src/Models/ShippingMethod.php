<?php

namespace Modules\Shipping\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Carriers\ShippingCarrier;
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
 * @property string|null $carrier
 * @property array<string, mixed>|null $carrier_options
 * @property int|null $cost
 * @property list<string>|null $zip_prefixes
 * @property list<int>|null $excluded_product_type_ids
 * @property list<int>|null $excluded_category_ids
 * @property list<string>|null $countries
 * @property string|null $delivery_time
 * @property int|null $min_order
 * @property int|null $max_order
 */
class ShippingMethod extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'description', 'pickup_details', 'delivery_time'];

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
            'carrier_options' => 'array',
            'cost' => 'integer',
            'zip_prefixes' => 'array',
            'excluded_product_type_ids' => 'array',
            'excluded_category_ids' => 'array',
            'countries' => 'array',
            'min_order' => 'integer',
            'max_order' => 'integer',
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

    /** Kuriér spôsobu dopravy (null = bez napojenia). */
    public function carrierService(): ?ShippingCarrier
    {
        return app(CarrierRegistry::class)->find($this->carrier);
    }

    /**
     * Doručuje sa do tejto krajiny? (prázdny zoznam = všade)
     */
    public function deliversToCountry(?string $country): bool
    {
        $countries = array_map('strtoupper', array_filter($this->countries ?? []));

        return $countries === [] || in_array(strtoupper((string) $country), $countries, true);
    }

    /**
     * Dá sa doprava použiť pre objednávku s touto sumou tovaru (v mene objednávky)?
     */
    public function allowsOrderValue(int $subtotal, ?string $currency = null): bool
    {
        $currency ??= app(CurrencyContext::class)->current();

        if ($this->min_order !== null && $subtotal < $this->amountIn('min_order', $this->min_order, $currency)) {
            return false;
        }

        return $this->max_order === null || $subtotal <= $this->amountIn('max_order', $this->max_order, $currency);
    }

    /**
     * Doručuje sa na toto PSČ? (vlastný rozvoz iba v okolí)
     */
    public function deliversToZip(?string $zip): bool
    {
        $prefixes = array_filter(array_map(fn ($prefix) => preg_replace('/\s+/', '', (string) $prefix), $this->zip_prefixes ?? []));

        if ($prefixes === []) {
            return true;
        }

        $zip = preg_replace('/\s+/', '', (string) $zip);

        foreach ($prefixes as $prefix) {
            if ($prefix !== '' && str_starts_with($zip, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Dá sa doprava použiť pre tieto produkty? (napr. zvieratá iba osobne)
     *
     * @param  list<int|null>  $productTypeIds
     * @param  list<int>  $categoryIds
     */
    public function allowsProducts(array $productTypeIds, array $categoryIds): bool
    {
        $types = array_map('intval', $this->excluded_product_type_ids ?? []);
        $categories = array_map('intval', $this->excluded_category_ids ?? []);

        return array_intersect($types, array_filter($productTypeIds)) === [] && array_intersect($categories, $categoryIds) === [];
    }
}
