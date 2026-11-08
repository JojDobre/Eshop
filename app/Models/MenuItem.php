<?php

namespace App\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Menus\Menus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Položka ponuky (menu) v mieste šablóny, napr. v hlavičke alebo päte.
 *
 * @property int $id
 * @property string $location
 * @property int|null $parent_id
 * @property string|null $label Vlastný názov (prázdny = názov cieľa, napr. kategórie).
 * @property string $type url, page, category, product…
 * @property string|null $target Adresa alebo ID cieľa.
 * @property bool $new_window
 * @property int $position
 */
class MenuItem extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['label'];

    protected $attributes = ['type' => 'url', 'new_window' => false, 'position' => 0];

    protected static function booted(): void
    {
        static::saved(fn () => app(Menus::class)->forget());
        static::deleted(fn () => app(Menus::class)->forget());
    }

    protected function casts(): array
    {
        return ['new_window' => 'boolean', 'position' => 'integer'];
    }

    /**
     * @return BelongsTo<MenuItem, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MenuItem, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /**
     * @param  Builder<MenuItem>  $query
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }
}
