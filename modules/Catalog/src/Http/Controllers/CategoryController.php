<?php

namespace Modules\Catalog\Http\Controllers;

use App\Core\Content\AlternateUrls;
use App\Core\Content\ShopLocales;
use App\Core\Themes\ThemeManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalog\CustomFields\CustomFieldFilters;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class CategoryController extends Controller
{
    use ProductListing;

    public function __invoke(Request $request, string $slug, CustomFieldFilters $filters): View
    {
        $category = Category::query()->visible()->whereSlug($slug)->firstOrFail();
        // Filtre a stránkovanie ostanú zachované aj v adrese pre iný jazyk.
        app(AlternateUrls::class)->using(fn () => $category->url().(request()->getQueryString() ? '?'.request()->getQueryString() : ''));
        $sort = $this->sortOption($request);
        $selected = $filters->input($request->query('f'));

        $base = Product::query()->visible()->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $category->descendantIdsWithSelf()));
        $available = $filters->available($base);

        $filtered = clone $base;
        $filters->apply($filtered, $selected);

        $products = $this->applySort($filtered, $sort)->paginate(24)->withQueryString();

        return view(app(ThemeManager::class)->template('category', ['category-'.$category->slugIn(app(ShopLocales::class)->default())]), [
            'category' => $category,
            'subcategories' => $category->children()->visible()->get(),
            'products' => $products,
            'sort' => $sort,
            'filters' => $available,
            'selectedFilters' => $selected,
        ]);
    }
}
