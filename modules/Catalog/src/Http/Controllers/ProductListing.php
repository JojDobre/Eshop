<?php

namespace Modules\Catalog\Http\Controllers;

use App\Core\Support\CurrencyContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Product;

/**
 * Spoločné triedenie a stránkovanie zoznamov produktov (kategória, vyhľadávanie).
 */
trait ProductListing
{
    /** @var list<string> */
    private array $sortOptions = ['newest', 'price_asc', 'price_desc', 'name'];

    private function sortOption(Request $request): string
    {
        $sort = (string) $request->query('sort', 'newest');

        return in_array($sort, $this->sortOptions, true) ? $sort : 'newest';
    }

    /**
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function applySort(Builder $query, string $sort): Builder
    {
        $query->select('products.*')->with(['prices', 'groupPrices', 'taxRate', 'variants' => fn ($variants) => $variants->where('is_active', true)->with(['prices', 'groupPrices'])]);

        return match ($sort) {
            'price_asc', 'price_desc' => $query
                ->leftJoin('product_prices as listing_price', function ($join): void {
                    $join->on('listing_price.product_id', '=', 'products.id')
                        ->where('listing_price.currency', app(CurrencyContext::class)->current());
                })
                ->orderBy('listing_price.amount', $sort === 'price_asc' ? 'asc' : 'desc')
                ->orderBy('products.id'),
            'name' => $query->orderBy('products.name->'.app(Product::class)->getLocale()),
            default => $query->latest('products.created_at')->orderByDesc('products.id'),
        };
    }
}
