<?php

namespace Modules\Catalog\Comparison;

use Illuminate\Support\Collection;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\Models\Product;

/**
 * Produkty v porovnávači (v relácii návštevníka). Na jeden typ produktu najviac 4, spolu najviac 12.
 */
class Comparison
{
    public const SESSION_KEY = 'catalog.comparison';

    public const MAX_PER_TYPE = 4;

    public const MAX = 12;

    public static function enabled(): bool
    {
        return CatalogFeatures::enabled('comparison');
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_map('intval', (array) session(self::SESSION_KEY, [])));
    }

    public function has(int $productId): bool
    {
        return in_array($productId, $this->ids(), true);
    }

    public function count(): int
    {
        return count($this->ids());
    }

    /**
     * Pridá produkt. Vráti false, ak je pre jeho typ (alebo celkovo) porovnávač plný.
     */
    public function add(Product $product): bool
    {
        $ids = $this->ids();

        if (in_array($product->id, $ids, true)) {
            return true;
        }

        $sameType = Product::query()->whereIn('id', $ids)->where(fn ($query) => $product->product_type_id === null
            ? $query->whereNull('product_type_id')
            : $query->where('product_type_id', $product->product_type_id))->count();

        if (count($ids) >= self::MAX || $sameType >= self::MAX_PER_TYPE) {
            return false;
        }

        session()->put(self::SESSION_KEY, [...$ids, $product->id]);

        return true;
    }

    public function remove(int $productId): void
    {
        session()->put(self::SESSION_KEY, array_values(array_diff($this->ids(), [$productId])));
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Viditeľné produkty v poradí pridania (aj zo zdieľaného odkazu).
     *
     * @param  list<int>|null  $ids
     * @return Collection<int, Product>
     */
    public function products(?array $ids = null): Collection
    {
        $ids = array_slice(array_values(array_unique(array_map('intval', $ids ?? $this->ids()))), 0, self::MAX);

        if ($ids === []) {
            return collect();
        }

        $products = Product::query()->visible()->whereIn('id', $ids)
            ->with(['prices', 'groupPrices', 'taxRate', 'manufacturer', 'productType', 'customFieldValues.field', 'variants.prices'])
            ->get()->keyBy('id');

        return collect($ids)->map(fn (int $id) => $products->get($id))->filter()->values();
    }
}
