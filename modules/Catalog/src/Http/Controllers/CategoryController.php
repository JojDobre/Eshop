<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class CategoryController extends Controller
{
    use ProductListing;

    public function __invoke(Request $request, string $slug): View
    {
        $category = Category::query()->visible()->whereSlug($slug)->firstOrFail();
        $sort = $this->sortOption($request);

        $products = $this->applySort(
            Product::query()->visible()->whereHas('categories', fn ($query) => $query->whereIn('categories.id', $category->descendantIdsWithSelf())),
            $sort,
        )->paginate(24)->withQueryString();

        return view('theme::templates.category', [
            'category' => $category,
            'subcategories' => $category->children()->visible()->get(),
            'products' => $products,
            'sort' => $sort,
        ]);
    }
}
