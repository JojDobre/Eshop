<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;

class SearchController extends Controller
{
    use ProductListing;

    public function __invoke(Request $request): View
    {
        $term = Str::limit(trim((string) $request->query('q', '')), 100, '');
        $sort = $this->sortOption($request);

        $products = mb_strlen($term) >= 2
            ? $this->applySort(Product::query()->visible()->search($term), $sort)->paginate(24)->withQueryString()
            : null;

        return view('theme::templates.search', [
            'term' => $term,
            'products' => $products,
            'sort' => $sort,
        ]);
    }
}
