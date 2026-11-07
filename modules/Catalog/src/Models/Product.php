<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Content\HasLocalizedSlug;
use App\Core\Content\LocalizedUrls;
use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldValue;
use Modules\Catalog\StockLots;
use Modules\Catalog\StockService;
use Modules\Tax\Models\TaxRate;
use Modules\Tax\TaxCalculator;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $sku
 * @property string|null $short_description
 * @property string|null $description
 * @property list<string>|null $images
 * @property bool $track_stock
 * @property int $stock_quantity
 * @property int|null $weight
 * @property bool $is_visible
 * @property string|null $search_text
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property-read Collection<int, ProductPrice> $prices
 * @property-read Collection<int, Category> $categories
 */
class Product extends Model
{
    use HasContentTranslations, HasLocalizedSlug;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'slug', 'short_description', 'description', 'safety_info', 'meta_title', 'meta_description'];

    /** Zmeny skladu cez StockService sa zapisujú samy, priame úpravy (formulár) sa zapíšu ako „edit". */
    private static bool $loggingStock = true;

    protected $attributes = [
        'track_stock' => true,
        'stock_quantity' => 0,
        'is_visible' => true,
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'track_stock' => 'boolean',
            'stock_quantity' => 'integer',
            'weight' => 'integer',
            'unit_amount' => 'decimal:4',
            'is_bundle' => 'boolean',
            'is_visible' => 'boolean',
            'low_stock_threshold' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $product): void {
            $product->search_text = static::normalizeForSearch(implode(' ', array_filter([
                ...array_values($product->getTranslations('name')),
                $product->sku,
                ...array_values($product->getTranslations('short_description')),
            ])));
        });

        // Priama zmena množstva (napr. vo formulári administrácie) sa tiež zapíše do histórie skladu.
        static::updated(function (Product $product): void {
            if (self::$loggingStock && $product->track_stock && $product->wasChanged('stock_quantity')) {
                $delta = $product->stock_quantity - (int) $product->getOriginal('stock_quantity');
                app(StockService::class)->record($product, $delta, StockMovement::EDIT);
            }
        });
    }

    /**
     * Vykoná zmenu skladu bez automatického zápisu (zapisuje ju volajúci, napr. StockService).
     */
    public static function withoutStockLogging(Closure $callback): mixed
    {
        $previous = self::$loggingStock;
        self::$loggingStock = false;

        try {
            return $callback();
        } finally {
            self::$loggingStock = $previous;
        }
    }

    /**
     * @return BelongsTo<TaxRate, $this>
     */
    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(TaxRate::class);
    }

    /**
     * @return BelongsTo<Manufacturer, $this>
     */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * @return HasMany<StockMovement, $this>
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }

    /**
     * Sadzba DPH produktu v percentách (sadzba produktu, inak predvolená sadzba obchodu).
     */
    public function vatRate(): float
    {
        if ($this->tax_rate_id !== null) {
            return (float) ($this->taxRate?->rate ?? 0);
        }

        return self::defaultVatRate();
    }

    /**
     * Predvolená sadzba DPH (načíta sa raz za požiadavku).
     */
    public static function defaultVatRate(): float
    {
        return once(fn () => (float) (TaxRate::default()?->rate ?? 0));
    }

    /**
     * @return HasMany<ProductPrice, $this>
     */
    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class)->whereNull('product_variant_id');
    }

    /**
     * @return HasMany<CustomFieldValue, $this>
     */
    public function customFieldValues(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'entity_id')
            ->where('entity_type', CustomField::PRODUCT);
    }

    /**
     * Parametre na zobrazenie zákazníkovi: [názov => hodnota].
     *
     * @return array<string, string>
     */
    public function visibleParameters(): array
    {
        $this->loadMissing('customFieldValues.field');

        return $this->customFieldValues
            ->filter(fn ($value) => $value->field !== null && $value->field->is_visible)
            ->sortBy(fn ($value) => [$value->field->position, $value->field->id])
            ->mapWithKeys(fn ($value) => [$value->field->name => $value->field->display($value->valueIn())])
            ->filter(fn ($display) => $display !== null)
            ->all();
    }

    /**
     * @return HasMany<ProductOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class)->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<ProductVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('position')->orderBy('id');
    }

    /**
     * Má produkt varianty? (Vtedy si zákazník musí vybrať variant a sklad sa sleduje pri variantoch.)
     */
    public function hasVariants(): bool
    {
        return $this->relationLoaded('variants') ? $this->variants->isNotEmpty() : $this->variants()->exists();
    }

    /**
     * Najnižšia cena produktu alebo jeho aktívnych variantov (pre „od X €" vo výpise).
     */
    public function priceFrom(?string $currency = null): ?int
    {
        $prices = [$this->priceIn($currency)];

        if ($this->hasVariants()) {
            $variants = $this->variants->where('is_active', true);
            $variants->loadMissing('prices');
            $prices = $variants->map(fn (ProductVariant $variant) => $variant->setRelation('product', $this)->priceIn($currency))->all();
        }

        $prices = array_filter($prices, fn ($price) => $price !== null);

        return $prices === [] ? null : min($prices);
    }

    /**
     * Majú varianty rôzne ceny?
     */
    public function hasVaryingPrices(?string $currency = null): bool
    {
        if (! $this->hasVariants()) {
            return false;
        }

        return $this->variants->where('is_active', true)
            ->map(fn (ProductVariant $variant) => $variant->setRelation('product', $this)->priceIn($currency))
            ->unique()->count() > 1;
    }

    /**
     * @return BelongsToMany<Category, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class)->withPivot('position');
    }

    /**
     * @param  Builder<Product>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * Vyhľadávanie bez ohľadu na veľké písmená a diakritiku. Všetky slová musia sedieť.
     *
     * @param  Builder<Product>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        foreach (preg_split('/\s+/', static::normalizeForSearch($term)) ?: [] as $word) {
            if ($word !== '') {
                $query->where('search_text', 'like', '%'.addcslashes($word, '%_\\').'%');
            }
        }
    }

    /**
     * Cena pre zákazníka v danej mene (najmenšie jednotky, s DPH), alebo null, ak cena nie je zadaná.
     */
    public function priceIn(?string $currency = null): ?int
    {
        return self::withExpiryDiscount($this->regularPriceIn($currency), app(StockLots::class)->expiryDiscountPercent($this, null));
    }

    /**
     * Cena bez automatickej zľavy pri blížiacej sa expirácii.
     */
    public function regularPriceIn(?string $currency = null): ?int
    {
        $currency ??= app(CurrencyContext::class)->current();
        $entered = $this->enteredPriceIn($currency);

        if ($entered !== null) {
            return app(TaxCalculator::class)->grossFromEntered($entered, $this->vatRate());
        }

        // Cena v mene chýba: prepočet z predvolenej meny kurzom (so zaokrúhlením meny).
        return $this->convertedFromDefault(fn (string $default) => $this->regularPriceIn($default), $currency);
    }

    /**
     * Zníži cenu o zľavu pri blížiacej sa expirácii.
     */
    public static function withExpiryDiscount(?int $price, int $percent): ?int
    {
        return $price === null || $percent === 0 ? $price : (int) round($price * (100 - $percent) / 100);
    }

    /**
     * @param  Closure(string): ?int  $inDefault
     */
    private function convertedFromDefault(Closure $inDefault, string $currency): ?int
    {
        $currencies = app(Currencies::class);
        $default = $currencies->default();

        if ($currency === $default || ! $currencies->hasRate($currency)) {
            return null;
        }

        $amount = $inDefault($default);

        return $amount === null ? null : $currencies->convert($amount, $default, $currency);
    }

    /**
     * Pôvodná (prečiarknutá) cena s DPH, ak je produkt v zľave.
     */
    public function compareAtPriceIn(?string $currency = null): ?int
    {
        $currency ??= app(CurrencyContext::class)->current();
        $row = $this->prices->firstWhere('currency', $currency);
        $price = $this->priceIn($currency);
        $regular = $this->regularPriceIn($currency);

        // Pri zľave pre blížiacu sa expiráciu sa prečiarkne bežná cena (ak nie je zadaná vyššia).
        if ($price !== null && $regular !== null && $regular > $price) {
            $compareAt = $row?->compare_at_amount !== null ? app(TaxCalculator::class)->grossFromEntered($row->compare_at_amount, $this->vatRate()) : null;

            return max($regular, $compareAt ?? 0);
        }

        if ($row === null) {
            // Bez ceny v mene: prepočítaná pôvodná cena z predvolenej meny.
            $gross = $this->convertedFromDefault(fn (string $default) => $this->compareAtPriceIn($default), $currency);

            return $gross !== null && $price !== null && $gross > $price ? $gross : null;
        }

        $compareAt = $row->compare_at_amount;

        if ($compareAt === null || $price === null) {
            return null;
        }

        $gross = app(TaxCalculator::class)->grossFromEntered($compareAt, $this->vatRate());

        return $gross > $price ? $gross : null;
    }

    /**
     * Cena tak, ako ju obchodník zadal (s DPH alebo bez DPH podľa nastavení).
     */
    public function enteredPriceIn(?string $currency = null): ?int
    {
        $currency ??= app(CurrencyContext::class)->current();

        return $this->prices->firstWhere('currency', $currency)?->amount;
    }

    public function enteredCompareAtPriceIn(?string $currency = null): ?int
    {
        $currency ??= app(CurrencyContext::class)->current();

        return $this->prices->firstWhere('currency', $currency)?->compare_at_amount;
    }

    /**
     * Uloží cenu (a voliteľne pôvodnú cenu pri zľave). Zmena ceny sa zapíše do histórie cien.
     */
    public function setPrice(string $currency, int $amount, ?int $compareAt = null): void
    {
        $previous = $this->prices()->where('currency', $currency)->value('amount');

        ProductPrice::query()->updateOrCreate(
            ['product_id' => $this->id, 'product_variant_id' => null, 'currency' => $currency],
            ['amount' => $amount, 'compare_at_amount' => $compareAt],
        );
        $this->unsetRelation('prices');

        if ($previous === null || (int) $previous !== $amount) {
            DB::table('product_price_history')->insert([
                'product_id' => $this->id,
                'currency' => $currency,
                'amount' => $amount,
                'recorded_at' => now(),
            ]);
        }
    }

    /**
     * Najnižšia cena za posledných 30 dní (s DPH) – zobrazuje sa pri zľave podľa smernice Omnibus.
     */
    public function lowestPriceIn30Days(?string $currency = null): ?int
    {
        $currency ??= app(CurrencyContext::class)->current();

        // Bez vlastnej ceny v mene sa história prepočíta z predvolenej meny.
        if ($this->prices->firstWhere('currency', $currency) === null) {
            $converted = $this->convertedFromDefault(fn (string $default) => $this->lowestPriceIn30Days($default), $currency);

            if ($converted !== null) {
                return $converted;
            }
        }

        $since = now()->subDays(30);
        $history = DB::table('product_price_history')
            ->where('product_id', $this->id)->where('currency', $currency);

        $amounts = (clone $history)->where('recorded_at', '>=', $since)->pluck('amount')->all();

        // Cena platná na začiatku obdobia (posledná zmena pred ním).
        $atStart = (clone $history)->where('recorded_at', '<', $since)->orderByDesc('recorded_at')->value('amount');

        if ($atStart !== null) {
            $amounts[] = $atStart;
        }

        if ($amounts === []) {
            return null;
        }

        return app(TaxCalculator::class)->grossFromEntered((int) min($amounts), $this->vatRate());
    }

    /**
     * Dá sa kúpiť dané množstvo?
     */
    public function isAvailable(int $quantity = 1): bool
    {
        if (! $this->is_visible) {
            return false;
        }

        // Balíček je dostupný, ak sú dostupné všetky zložky v potrebnom počte.
        if ($this->isBundle()) {
            return $this->bundleItems->isNotEmpty() && $this->bundleItems->every(fn (BundleItem $item) => $item->variant
                ? $item->variant->setRelation('product', $item->product)->isAvailable($quantity * $item->quantity)
                : (bool) $item->product?->isAvailable($quantity * $item->quantity));
        }

        // Produkt s variantmi je dostupný, ak je dostupný aspoň jeden variant.
        if ($this->hasVariants()) {
            return $this->variants->contains(fn (ProductVariant $variant) => $variant->setRelation('product', $this)->isAvailable($quantity));
        }

        return ! $this->track_stock || $this->sellableStock() >= $quantity;
    }

    /**
     * Počet kusov, ktoré sa dajú predať (bez tovaru v neaktívnych skladoch a expirovaného).
     */
    public function sellableStock(): int
    {
        return max(0, $this->stock_quantity - app(StockLots::class)->blockedQuantity($this, null));
    }

    /**
     * Najväčšie množstvo, ktoré sa dá kúpiť (null = bez obmedzenia skladom).
     */
    /**
     * Doplnkové voľby k produktu (zobrazia sa iba so zapnutou funkciou).
     *
     * @return BelongsToMany<Addon, $this>
     */
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class);
    }

    /**
     * @return Collection<int, Addon>
     */
    public function availableAddons(): Collection
    {
        return CatalogFeatures::enabled('addons') ? $this->addons()->active()->get() : collect();
    }

    /**
     * Zložky balíčka.
     *
     * @return HasMany<BundleItem, $this>
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(BundleItem::class, 'bundle_id')->orderBy('position')->orderBy('id');
    }

    /**
     * Zásoby po skladoch a šaržiach.
     *
     * @return HasMany<StockLot, $this>
     */
    public function stockLots(): HasMany
    {
        return $this->hasMany(StockLot::class);
    }

    /** Je produkt balíček? (iba so zapnutou funkciou balíčkov) */
    public function isBundle(): bool
    {
        return $this->is_bundle && CatalogFeatures::enabled('bundles');
    }

    /**
     * Súčet cien zložiek (na zobrazenie úspory pri balíčku).
     */
    public function bundleComponentsPrice(?string $currency = null): ?int
    {
        if (! $this->isBundle()) {
            return null;
        }

        $sum = 0;

        foreach ($this->bundleItems as $item) {
            $price = $item->variant ? $item->variant->setRelation('product', $item->product)->priceIn($currency) : $item->product?->priceIn($currency);

            if ($price === null) {
                return null;
            }

            $sum += $price * $item->quantity;
        }

        return $sum;
    }

    public function maxPurchasableQuantity(): ?int
    {
        $max = $this->track_stock ? $this->sellableStock() : null;

        // Balíček: koľko kompletných balíčkov sa dá zložiť zo skladu zložiek.
        if ($this->isBundle()) {
            $max = null;

            foreach ($this->bundleItems as $item) {
                $componentMax = $item->variant ? $item->variant->setRelation('product', $item->product)->maxPurchasableQuantity() : $item->product?->maxPurchasableQuantity();

                if ($componentMax !== null) {
                    $max = min($max ?? PHP_INT_MAX, intdiv($componentMax, max(1, $item->quantity)));
                }
            }
        }

        // Jedinečný kus (napr. zviera) sa dá kúpiť iba raz.
        return $this->isUnique() ? min(1, $max ?? 1) : $max;
    }

    /**
     * Typ produktu (iba ak je funkcia typov zapnutá).
     *
     * @return BelongsTo<ProductType, $this>
     */
    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class);
    }

    private function activeType(): ?ProductType
    {
        return $this->product_type_id !== null && CatalogFeatures::enabled('product_types') ? $this->productType : null;
    }

    /** Potrebuje tovar dopravu? (služby a digitálne produkty nie) */
    public function requiresShipping(): bool
    {
        return $this->activeType()?->requires_shipping ?? true;
    }

    public function isUnique(): bool
    {
        return (bool) $this->activeType()?->is_unique;
    }

    /** Merná jednotka ceny za jednotku (kg, l, m), alebo null. */
    public function unit(): ?string
    {
        $type = $this->activeType();

        return $type !== null && $type->hasUnitPrice() && (float) $this->unit_amount > 0 ? $type->unit : null;
    }

    /**
     * Cena za mernú jednotku (napr. za 1 kg) s DPH.
     */
    public function unitPriceIn(?string $currency = null): ?int
    {
        $price = $this->priceIn($currency);

        return $price === null || $this->unit() === null ? null : (int) round($price / (float) $this->unit_amount);
    }

    public function url(): string
    {
        return route('catalog.product', $this->slugIn(app(LocalizedUrls::class)->current()) ?? $this->slug);
    }

    /**
     * @return list<string>
     */
    public function imageUrls(): array
    {
        return array_values(array_map(
            fn (string $path) => Storage::disk('public')->url($path),
            array_filter($this->images ?? [], 'is_string'),
        ));
    }

    public function mainImageUrl(): ?string
    {
        return $this->imageUrls()[0] ?? null;
    }

    public static function normalizeForSearch(string $text): string
    {
        return Str::of(strip_tags($text))->ascii()->lower()->squish()->toString();
    }
}
