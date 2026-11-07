<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Možnosť produktu, napr. „Veľkosť".
 *
 * @property int $id
 * @property int $product_id
 * @property string $name
 * @property int $position
 * @property-read Collection<int, ProductOptionValue> $values
 */
class ProductOption extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name'];

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<ProductOptionValue, $this>
     */
    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class)->orderBy('position')->orderBy('id');
    }
}
