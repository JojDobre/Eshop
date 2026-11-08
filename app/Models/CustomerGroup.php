<?php

namespace App\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Zákaznícka skupina. Ceny pre skupiny a množstevné zľavy sú v module katalógu.
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property float $discount_percent
 * @property bool $prices_without_vat
 * @property bool $is_default
 * @property int $position
 */
class CustomerGroup extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name'];

    protected $attributes = ['discount_percent' => 0, 'prices_without_vat' => false, 'is_default' => false, 'position' => 0];

    protected function casts(): array
    {
        return ['discount_percent' => 'float', 'prices_without_vat' => 'boolean', 'is_default' => 'boolean', 'position' => 'integer'];
    }

    protected static function booted(): void
    {
        // Predvolená môže byť iba jedna skupina.
        static::saved(function (CustomerGroup $group): void {
            if ($group->is_default) {
                static::query()->whereKeyNot($group->id)->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    /**
     * @param  Builder<CustomerGroup>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    /** Cena po všeobecnej zľave skupiny. */
    public function applyDiscount(int $price): int
    {
        return $this->discount_percent > 0 ? (int) round($price * (100 - $this->discount_percent) / 100) : $price;
    }
}
