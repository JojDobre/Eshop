<?php

namespace Modules\Catalog\Models;

use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\StockLots;
use Modules\Catalog\StockService;
use Modules\Tax\TaxCalculator;

/**
 * Variant produktu – jedna kombinácia hodnôt možností (napr. M / červená).
 *
 * Sklad sa pri produktoch s variantmi sleduje pri variantoch. Cena, ktorú variant nemá zadanú,
 * sa preberá z produktu.
 *
 * @property int $id
 * @property int $product_id
 * @property string|null $sku
 * @property int $stock_quantity
 * @property int|null $weight
 * @property list<string>|null $images
 * @property bool $is_active
 * @property int $position
 * @property-read Product $product
 * @property-read Collection<int, ProductOptionValue> $values
 * @property-read Collection<int, ProductPrice> $prices
 */
class ProductVariant extends Model
{
    protected $guarded = [];

    /** Priame zmeny skladu (formulár) sa zapisujú do histórie, zmeny cez StockService si zapisuje služba. */
    private static bool $loggingStock = true;

    protected static function booted(): void
    {
        static::updated(function (ProductVariant $variant): void {
            if (self::$loggingStock && $variant->wasChanged('stock_quantity') && $variant->product?->track_stock) {
                $delta = $variant->stock_quantity - (int) $variant->getOriginal('stock_quantity');
                app(StockService::class)->record($variant->product, $delta, StockMovement::EDIT, variant: $variant);
            }
        });
    }

    public static function withoutStockLogging(\Closure $callback): mixed
    {
        $previous = self::$loggingStock;
        self::$loggingStock = false;

        try {
            return $callback();
        } finally {
            self::$loggingStock = $previous;
        }
    }

    protected $attributes = [
        'stock_quantity' => 0,
        'is_active' => true,
        'position' => 0,
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'stock_quantity' => 'integer',
            'weight' => 'integer',
            'is_active' => 'boolean',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return BelongsToMany<ProductOptionValue, $this>
     */
    public function values(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'product_variant_values')
            ->join('product_options', 'product_options.id', '=', 'product_option_values.product_option_id')
            ->orderBy('product_options.position')->orderBy('product_options.id')
            ->select('product_option_values.*');
    }

    /**
     * @return HasMany<ProductPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    /**
     * Názov variantu z hodnôt, napr. „M / červená".
     */
    public function name(): string
    {
        return $this->values->map(fn (ProductOptionValue $value) => $value->value)->implode(' / ');
    }

    public function enteredPriceIn(?string $currency = null): ?int
    {
        $currency ??= app(CurrencyContext::class)->current();

        return $this->prices->firstWhere('currency', $currency)?->amount;
    }

    /**
     * Cena variantu pre zákazníka (s DPH); ak variant nemá vlastnú cenu, cena produktu.
     */
    public function priceIn(?string $currency = null): ?int
    {
        return Product::withExpiryDiscount($this->regularPriceIn($currency), app(StockLots::class)->expiryDiscountPercent($this->product, $this));
    }

    /**
     * Cena bez automatickej zľavy pri blížiacej sa expirácii.
     */
    public function regularPriceIn(?string $currency = null): ?int
    {
        $currency ??= app(CurrencyContext::class)->current();
        $entered = $this->enteredPriceIn($currency);

        if ($entered !== null) {
            return app(TaxCalculator::class)->grossFromEntered($entered, $this->product->vatRate());
        }

        // Vlastná cena variantu v predvolenej mene sa prepočíta kurzom, inak cena produktu.
        $currencies = app(Currencies::class);
        $default = $currencies->default();

        if ($currency !== $default && $this->enteredPriceIn($default) !== null && $currencies->hasRate($currency)) {
            return $currencies->convert((int) $this->regularPriceIn($default), $default, $currency);
        }

        return $this->product->regularPriceIn($currency);
    }

    public function setPrice(string $currency, ?int $amount): void
    {
        if ($amount === null) {
            $this->prices()->where('currency', $currency)->delete();
        } else {
            ProductPrice::query()->updateOrCreate(
                ['product_id' => $this->product_id, 'product_variant_id' => $this->id, 'currency' => $currency],
                ['amount' => $amount],
            );
        }

        $this->unsetRelation('prices');
    }

    public function isAvailable(int $quantity = 1): bool
    {
        return $this->is_active
            && $this->product->is_visible
            && (! $this->product->track_stock || $this->sellableStock() >= $quantity || $this->product->canPreorder($quantity, $this->sellableStock(), $this->stock_quantity));
    }

    /**
     * Počet kusov, ktoré sa dajú predať (bez tovaru v neaktívnych skladoch a expirovaného).
     */
    public function sellableStock(): int
    {
        return max(0, $this->stock_quantity - app(StockLots::class)->blockedQuantity($this->product, $this));
    }

    /** Bude daný počet kusov (aspoň sčasti) predobjednávkou? */
    public function isPreorder(int $quantity = 1): bool
    {
        return $this->product->acceptsPreorders() && $this->sellableStock() < $quantity;
    }

    public function maxPurchasableQuantity(): ?int
    {
        $max = $this->product->track_stock ? $this->sellableStock() : null;

        if ($max !== null && $this->product->acceptsPreorders()) {
            $max = $this->product->preorderMax($max, $this->stock_quantity);
        }

        return $this->product->isUnique() ? min(1, $max ?? 1) : $max;
    }

    /**
     * Obrázky variantu, alebo obrázky produktu, ak variant vlastné nemá.
     *
     * @return list<string>
     */
    public function imageUrls(): array
    {
        $own = array_filter($this->images ?? [], 'is_string');

        if ($own === []) {
            return $this->product->imageUrls();
        }

        return array_values(array_map(fn (string $path) => Storage::disk('public')->url($path), $own));
    }
}
