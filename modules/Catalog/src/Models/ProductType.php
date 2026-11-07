<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\CustomFields\CustomField;

/**
 * @property int $id
 * @property string $name
 * @property bool $requires_shipping
 * @property bool $is_unique
 * @property string $unit
 */
class ProductType extends Model
{
    use HasContentTranslations;

    public const UNITS = ['pcs', 'kg', 'l', 'm'];

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['requires_shipping' => 'boolean', 'is_unique' => 'boolean', 'position' => 'integer'];
    }

    /**
     * @param  Builder<ProductType>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /**
     * Vlastné polia typu (žiadne = produkt má všetky polia).
     *
     * @return BelongsToMany<CustomField, $this>
     */
    public function customFields(): BelongsToMany
    {
        return $this->belongsToMany(CustomField::class, 'product_type_custom_field');
    }

    /**
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function hasUnitPrice(): bool
    {
        return $this->unit !== 'pcs';
    }
}
