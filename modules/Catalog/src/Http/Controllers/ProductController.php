<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Catalog\Models\Product;

class ProductController extends Controller
{
    public function __invoke(string $slug): View
    {
        $product = Product::query()->visible()->whereSlug($slug)->with(['prices', 'categories'])->firstOrFail();
        $category = $product->categories->firstWhere('is_visible', true);

        $related = $category === null ? collect() : Product::query()->visible()
            ->whereKeyNot($product->id)
            ->whereHas('categories', fn ($query) => $query->whereKey($category->id))
            ->with('prices')
            ->latest()
            ->limit(4)
            ->get();

        return view('theme::templates.product', [
            'product' => $product,
            'category' => $category,
            'relatedProducts' => $related,
        ]);
    }
}
