<?php

namespace Modules\Marketing\Http\Controllers;

use App\Core\Content\ContentLocale;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Cart\CartManager;
use Modules\Cart\Models\Cart;
use Modules\Marketing\AbandonedCarts;

class AbandonedCartController extends Controller
{
    /**
     * E-mail zadaný v pokladni sa uloží ku košíku (pre pripomienku, ak objednávka nebude dokončená).
     */
    public function email(Request $request, CartManager $cart): JsonResponse
    {
        if (! AbandonedCarts::enabled()) {
            return response()->json(['saved' => false]);
        }

        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $current = $cart->current();
        $current?->forceFill(['email' => mb_strtolower(trim($data['email'])), 'locale' => app(ContentLocale::class)->current(), 'reminded_at' => null])->save();

        return response()->json(['saved' => $current !== null]);
    }

    /**
     * Odkaz z pripomienky: obnoví košík v novom prehliadači alebo zariadení.
     */
    public function restore(string $token): RedirectResponse
    {
        $cart = Cart::query()->where('token', $token)->firstOrFail();
        session()->put(CartManager::SESSION_KEY, $cart->token);

        return redirect()->route('cart.show');
    }
}
