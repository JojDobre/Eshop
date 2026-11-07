<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Support\Currencies;
use App\Core\Support\CurrencyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Doplnková voľba k produktu (darčekové balenie, gravírovanie…).
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $price
 * @property string $currency
 * @property bool $needs_text
 * @property string|null $text_label
 * @property int $text_max_length
 * @property bool $is_active
 */
class Addon extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'description', 'text_label'];

    protected function casts(): array
    {
        return ['price' => 'integer', 'needs_text' => 'boolean', 'is_active' => 'boolean', 'text_max_length' => 'integer', 'position' => 'integer'];
    }

    /**
     * @param  Builder<Addon>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    /** Príplatok v mene (prepočet kurzom so zaokrúhlením meny). */
    public function priceIn(?string $currency = null): int
    {
        $currency ??= app(CurrencyContext::class)->current();

        return $this->price === 0 ? 0 : app(Currencies::class)->convert($this->price, $this->currency, $currency);
    }
}
