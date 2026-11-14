<?php

namespace Modules\Catalog\Models;

use App\Core\Content\HasContentTranslations;
use App\Core\Content\HasLocalizedSlug;
use App\Core\Content\LocalizedUrls;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $image
 * @property int $position
 * @property bool $is_visible
 * @property-read Category|null $parent
 * @property-read Collection<int, Category> $children
 */
class Category extends Model
{
    use HasContentTranslations, HasLocalizedSlug;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'slug', 'description', 'meta_title', 'meta_description', 'blocks'];

    protected $attributes = [
        'position' => 0,
        'is_visible' => true,
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'display' => 'array',
            'position' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('position')->orderBy('id');
    }

    /**
     * @return BelongsToMany<Product, $this>
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class)->withPivot('position');
    }

    /**
     * @param  Builder<Category>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->where('is_visible', true);
    }

    /**
     * @param  Builder<Category>  $query
     */
    public function scopeRoots(Builder $query): void
    {
        $query->whereNull('parent_id')->orderBy('position')->orderBy('id');
    }

    /**
     * Nadradené kategórie od najvyššej (pre navigačnú cestu).
     *
     * @return list<Category>
     */
    public function ancestors(): array
    {
        $ancestors = [];
        $current = $this->parent;
        $seen = [$this->id => true];

        while ($current !== null && ! isset($seen[$current->id])) {
            $seen[$current->id] = true;
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /**
     * ID tejto kategórie a všetkých podkategórií (produkty podkategórií sa zobrazujú aj v nadradenej).
     *
     * @return list<int>
     */
    public function descendantIdsWithSelf(): array
    {
        $ids = [$this->id];
        $parents = [$this->id];

        while ($parents !== []) {
            $parents = static::query()->whereIn('parent_id', $parents)->whereNotIn('id', $ids)->pluck('id')->all();
            $ids = [...$ids, ...$parents];
        }

        return $ids;
    }

    /**
     * Názov s nadradenými kategóriami, napr. „Káva › Zrnková".
     */
    public function pathName(): string
    {
        return collect([...$this->ancestors(), $this])->map(fn (Category $category) => $category->name)->implode(' › ');
    }

    public function url(): string
    {
        return route('catalog.category', $this->slugIn(app(LocalizedUrls::class)->current()) ?? $this->slug);
    }

    public function imageUrl(): ?string
    {
        return $this->image ? Storage::disk('public')->url($this->image) : null;
    }
}
