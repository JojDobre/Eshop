<?php

namespace Modules\Cart\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cart\CartException;
use Modules\Cart\CartManager;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

class CartController extends Controller
{
    public function __construct(private readonly CartManager $cart) {}

    public function show(): View
    {
        return view('theme::templates.cart', ['summary' => $this->cart->summary()]);
    }

    /**
     * Pridanie do košíka. Pri požiadavke z JavaScriptu (bez obnovenia stránky) vráti JSON s počtom kusov
     * v košíku a textom upozornenia, inak presmeruje späť.
     */
    public function add(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.CartManager::MAX_QUANTITY],
            'variant_id' => ['nullable', 'integer'],
            'addons' => ['nullable', 'array'],
            'addon_texts' => ['nullable', 'array'],
            'addon_texts.*' => ['nullable', 'string', 'max:500'],
        ]);

        // Zaškrtnuté doplnkové voľby: id => text (pri voľbách s textom).
        $addons = collect($data['addons'] ?? [])->filter(fn ($checked) => (bool) $checked)
            ->mapWithKeys(fn ($checked, $id) => [(int) $id => $data['addon_texts'][$id] ?? null])->all();

        $product = Product::query()->with(['prices', 'variants'])->find($data['product_id']);

        if ($product === null) {
            return $this->addFailed($request, __('cart::messages.item_not_found'));
        }

        try {
            $variant = isset($data['variant_id'])
                ? ProductVariant::query()->with(['prices', 'values'])->where('product_id', $product->id)->find($data['variant_id'])
                : null;

            if (isset($data['variant_id']) && $variant === null) {
                throw new CartException(__('cart::messages.unavailable', ['product' => $product->name]));
            }

            $this->cart->add($product, (int) ($data['quantity'] ?? 1), $variant, $addons);
        } catch (CartException $e) {
            return $this->addFailed($request, $e->getMessage());
        }

        $name = isset($variant) && $variant ? $product->name.' ('.$variant->name().')' : $product->name;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('theme::shop.added_to_cart', ['product' => $name]),
                'count' => $this->cart->itemCount(),
                'cart_url' => route('cart.show'),
                'cart_label' => __('theme::shop.go_to_cart'),
            ]);
        }

        return back()->with('cart_added', $name);
    }

    private function addFailed(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['message' => $message], 422)
            : back()->with('error', $message);
    }

    public function update(Request $request, int $item): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:'.CartManager::MAX_QUANTITY]]);

        try {
            $this->cart->update($item, (int) $data['quantity']);
        } catch (CartException $e) {
            return redirect()->route('cart.show')->with('error', $e->getMessage());
        }

        return redirect()->route('cart.show');
    }

    public function remove(int $item): RedirectResponse
    {
        try {
            $this->cart->remove($item);
        } catch (CartException $e) {
            return redirect()->route('cart.show')->with('error', $e->getMessage());
        }

        return redirect()->route('cart.show')->with('success', __('cart::messages.removed'));
    }
}
