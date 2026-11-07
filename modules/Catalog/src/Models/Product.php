<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Content\HasLocalizedSlug;
use App\Core\Support\CurrencyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
    public array $translatable = ['name', 'slug', 'short_description', 'description', 'meta_title', 'meta_description'];

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
     * Cena v danej mene (najmenšie jednotky, s DPH), alebo null, ak cena nie je zadaná.
     */
    public function priceIn(?string $currency = null): ?int
    {
        $currency ??= app(CurrencyContext::class)->current();

        return $this->prices->firstWhere('currency', $currency)?->amount;
    }

    public function setPrice(string $currency, int $amount): void
    {
        $this->prices()->updateOrCreate(['currency' => $currency], ['amount' => $amount]);
        $this->unsetRelation('prices');
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
