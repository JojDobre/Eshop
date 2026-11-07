<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Catalog\StockLots;

/**
 * Sklad alebo predajňa.
 *
 * @property int $id
 * @property string $name
 * @property string|null $code
 * @property string|null $address
 * @property bool $is_active Z neaktívneho skladu sa nepredáva.
 * @property int $position Poradie, v akom sa sklady vyprázdňujú pri objednávke.
 */
class Warehouse extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $attributes = ['is_active' => true, 'position' => 0];

    protected static function booted(): void
    {
        // Načítané zásoby v pamäti sa po zmene zabudnú.
        static::saved(fn () => app(StockLots::class)->forget());
        static::deleted(fn () => app(StockLots::class)->forget());
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'position' => 'integer'];
    }

    /**
     * @param  Builder<Warehouse>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /**
     * @return HasMany<StockLot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(StockLot::class);
    }

    /** Predvolený sklad (prvý v poradí) – sem ide tovar bez určeného skladu. */
    public static function default(): self
    {
        return self::query()->ordered()->first() ?? self::query()->create(['name' => __('catalog::admin.warehouse.default_name')]);
    }
}
