<?php

namespace Modules\Catalog\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Catalog\Comparison\Comparison;
use Modules\Catalog\Comparison\ComparisonTable;
use Modules\Catalog\Models\Product;

class ComparisonController extends Controller
{
    public function __construct(private readonly Comparison $comparison) {}

    /**
     * Porovnanie; s parametrom ?p=1,2,3 zobrazí zdieľané porovnanie (bez zmeny vlastného zoznamu).
     */
    public function show(Request $request): View
    {
        abort_unless(Comparison::enabled(), 404);

        $shared = $request->filled('p') ? array_filter(array_map('intval', explode(',', (string) $request->query('p')))) : null;
        $products = $this->comparison->products($shared);

        return view('theme::templates.compare', [
            'groups' => ComparisonTable::build($products),
            'shared' => $shared !== null,
            'shareUrl' => $products->isEmpty() ? null : route('compare.show', ['p' => $products->pluck('id')->implode(',')]),
        ]);
    }

    public function add(Request $request): RedirectResponse|JsonResponse
    {
        abort_unless(Comparison::enabled(), 404);

        $product = Product::query()->visible()->findOrFail($request->integer('product_id'));
        $added = $this->comparison->add($product);
        $message = $added ? __('catalog::shop.compare_added', ['product' => $product->name]) : __('catalog::shop.compare_full', ['max' => Comparison::MAX_PER_TYPE]);

        if ($request->wantsJson()) {
            return response()->json(['added' => $added, 'count' => $this->comparison->count(), 'message' => $message], $added ? 200 : 422);
        }

        return back()->with($added ? 'success' : 'error', $message);
    }

    public function remove(Request $request, int $product): RedirectResponse
    {
        $this->comparison->remove($product);

        return back()->with('success', __('catalog::shop.compare_removed'));
    }

    public function clear(): RedirectResponse
    {
        $this->comparison->clear();

        return redirect()->route('compare.show');
    }
}
