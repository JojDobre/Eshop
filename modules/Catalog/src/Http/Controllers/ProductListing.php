<?php

namespace Modules\Catalog\Http\Controllers;

use App\Core\Content\ContentLocale;
use App\Core\Support\Currencies;
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
            'price_asc', 'price_desc' => $this->orderByPrice($query, $sort === 'price_asc' ? 'asc' : 'desc'),
            'name' => $this->orderByName($query),
            default => $query->latest('products.created_at')->orderByDesc('products.id'),
        };
    }

    /**
     * Triedenie podľa ceny v zobrazenej mene. Produkt bez ceny zadanej v tejto mene sa zaradí podľa ceny
     * v predvolenej mene prepočítanej kurzom – rovnako, ako sa jeho cena zobrazuje zákazníkovi.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function orderByPrice(Builder $query, string $direction): Builder
    {
        $currency = app(CurrencyContext::class)->current();
        $default = app(Currencies::class)->default();

        $query->leftJoin('product_prices as listing_price', function ($join) use ($currency): void {
            $join->on('listing_price.product_id', '=', 'products.id')->where('listing_price.currency', $currency);
        });

        if ($currency === $default) {
            return $query->orderByRaw('listing_price.amount IS NULL')->orderBy('listing_price.amount', $direction)->orderBy('products.id');
        }

        // Koľko najmenších jednotiek zobrazenej meny zodpovedá jednej najmenšej jednotke predvolenej meny.
        $factor = app(Currencies::class)->convert(1_000_000, $default, $currency, false) / 1_000_000;

        return $query
            ->leftJoin('product_prices as listing_default', function ($join) use ($default): void {
                $join->on('listing_default.product_id', '=', 'products.id')->where('listing_default.currency', $default);
            })
            ->orderByRaw('COALESCE(listing_price.amount, listing_default.amount * ?) IS NULL', [$factor])
            ->orderByRaw('COALESCE(listing_price.amount, listing_default.amount * ?) '.($direction === 'asc' ? 'asc' : 'desc'), [$factor])
            ->orderBy('products.id');
    }

    /**
     * Triedenie podľa názvu v jazyku stránky; produkt bez prekladu sa zaradí podľa názvu v predvolenom jazyku.
     *
     * @param  Builder<Product>  $query
     * @return Builder<Product>
     */
    private function orderByName(Builder $query): Builder
    {
        $grammar = $query->getQuery()->getGrammar();
        $locale = app(Product::class)->getLocale();
        $default = app(ContentLocale::class)->default();

        return $query
            ->orderByRaw('COALESCE('.$grammar->wrap('products.name->'.$locale).', '.$grammar->wrap('products.name->'.$default).')')
            ->orderBy('products.id');
    }
}
