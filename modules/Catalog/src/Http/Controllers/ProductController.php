<?php

namespace Modules\Catalog\Http\Controllers;

use App\Core\Content\AlternateUrls;
use App\Core\Content\ShopLocales;
use App\Core\Themes\ThemeManager;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controller;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

class ProductController extends Controller
{
    public function __invoke(string $slug): View
    {
        $product = Product::query()->visible()->whereSlug($slug)
            ->with(['prices', 'categories', 'taxRate', 'manufacturer', 'options.values', 'variants' => fn ($query) => $query->where('is_active', true)->with(['values', 'prices'])])
            ->firstOrFail();
        $category = $product->categories->firstWhere('is_visible', true);
        app(AlternateUrls::class)->using(fn () => $product->url());

        $related = $category === null ? collect() : Product::query()->visible()
            ->whereKeyNot($product->id)
            ->whereHas('categories', fn ($query) => $query->whereKey($category->id))
            ->with('prices')
            ->latest()
            ->limit(4)
            ->get();

        return view(app(ThemeManager::class)->template('product', ['product-'.$product->slugIn(app(ShopLocales::class)->default()), $product->product_type_id ? 'product-type-'.$product->product_type_id : null]), [
            'product' => $product,
            'variantData' => $product->hasVariants() ? $this->variantData($product) : null,
            'category' => $category,
            'relatedProducts' => $related,
        ]);
    }

    /**
     * Údaje pre výber variantu v prehliadači (možnosti, varianty, ceny, sklad, obrázky).
     *
     * @return array<string, mixed>
     */
    private function variantData(Product $product): array
    {
        return [
            'options' => $product->options->map(fn ($option) => [
                'id' => $option->id,
                'name' => $option->name,
                'values' => $option->values->map(fn ($value) => ['id' => $value->id, 'value' => $value->value, 'color' => $value->color])->values(),
            ])->values(),
            'variants' => $product->variants->map(function (ProductVariant $variant) use ($product) {
                $variant->setRelation('product', $product);
                $price = $variant->priceIn();

                return [
                    'id' => $variant->id,
                    'values' => $variant->values->pluck('id')->values(),
                    'price' => $price === null ? null : format_price($price),
                    'available' => $price !== null && $variant->isAvailable(),
                    'max' => $variant->maxPurchasableQuantity(),
                    'preorder' => $variant->isPreorder(),
                    'images' => array_filter($variant->images ?? []) === [] ? null : $variant->imageUrls(),
                ];
            })->values(),
        ];
    }
}
