<?php

namespace Modules\Cart\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cart\CartException;
use Modules\Cart\CartManager;
use Modules\Catalog\Models\Product;

class CartController extends Controller
{
    public function __construct(private readonly CartManager $cart) {}

    public function show(): View
    {
        return view('theme::templates.cart', ['summary' => $this->cart->summary()]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:'.CartManager::MAX_QUANTITY],
        ]);

        $product = Product::query()->with('prices')->find($data['product_id']);

        if ($product === null) {
            return back()->with('error', __('cart::messages.item_not_found'));
        }

        try {
            $this->cart->add($product, (int) ($data['quantity'] ?? 1));
        } catch (CartException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('cart_added', $product->name);
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
