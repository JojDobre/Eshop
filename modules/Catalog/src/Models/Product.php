<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Content\HasLocalizedSlug;
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
        return $this->hasMany(ProductPrice::class);
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
        $entered = $this->enteredPriceIn($currency);

        return $entered === null ? null : app(TaxCalculator::class)->grossFromEntered($entered, $this->vatRate());
    }

    /**
     * Pôvodná (prečiarknutá) cena s DPH, ak je produkt v zľave.
     */
    public function compareAtPriceIn(?string $currency = null): ?int
    {
        $currency ??= app(CurrencyContext::class)->current();
        $compareAt = $this->prices->firstWhere('currency', $currency)?->compare_at_amount;
        $price = $this->priceIn($currency);

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

        $this->prices()->updateOrCreate(['currency' => $currency], ['amount' => $amount, 'compare_at_amount' => $compareAt]);
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
        return $this->is_visible && (! $this->track_stock || $this->stock_quantity >= $quantity);
    }

    /**
     * Najväčšie množstvo, ktoré sa dá kúpiť (null = bez obmedzenia skladom).
     */
    public function maxPurchasableQuantity(): ?int
    {
        return $this->track_stock ? max(0, $this->stock_quantity) : null;
    }

    public function url(): string
    {
        return route('catalog.product', $this->slug);
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
