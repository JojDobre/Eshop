<?php

namespace Modules\Cart;

use App\Core\Support\CurrencyContext;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Str;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Catalog\Models\Product;

/**
 * Košík aktuálneho návštevníka. Identifikuje sa náhodným tokenom uloženým v session.
 */
class CartManager
{
    public const SESSION_KEY = 'cart_token';

    /** Najväčší počet kusov jedného produktu v košíku (ochrana pred omylom). */
    public const MAX_QUANTITY = 999;

    private ?Cart $cart = null;

    /** Token, pre ktorý je načítaný $cart (false = ešte nenačítané). */
    private string|false|null $resolvedToken = false;

    public function __construct(
        private readonly Session $session,
        private readonly CurrencyContext $currency,
    ) {}

    public function current(): ?Cart
    {
        $token = $this->session->get(self::SESSION_KEY);
        $token = is_string($token) ? $token : null;

        // Košík sa načíta znova, ak sa medzitým zmenila session (iný návštevník, odhlásenie a pod.).
        if ($this->resolvedToken !== $token) {
            $this->cart = $token !== null ? Cart::query()->where('token', $token)->first() : null;
            $this->resolvedToken = $token;
        }

        return $this->cart;
    }

    public function add(Product $product, int $quantity = 1): CartItem
    {
        $quantity = max(1, $quantity);

        $this->ensurePurchasable($product);

        $cart = $this->currentOrCreate();
        $item = $cart->items()->firstOrNew(['product_id' => $product->id], ['quantity' => 0]);
        $newQuantity = min(self::MAX_QUANTITY, $item->quantity + $quantity);

        $this->ensureStock($product, $newQuantity);

        $item->quantity = $newQuantity;
        $item->save();
        $cart->touch();

        return $item;
    }

    public function update(int $itemId, int $quantity): void
    {
        $item = $this->findItem($itemId);

        if ($quantity <= 0) {
            $item->delete();

            return;
        }

        $quantity = min(self::MAX_QUANTITY, $quantity);
        $this->ensureStock($item->product, $quantity);

        $item->update(['quantity' => $quantity]);
    }

    public function remove(int $itemId): void
    {
        $this->findItem($itemId)->delete();
    }

    /**
     * Vyprázdni košík (po odoslaní objednávky).
     */
    public function clear(): void
    {
        $this->current()?->delete();
        $this->session->forget(self::SESSION_KEY);
        $this->cart = null;
        $this->resolvedToken = null;
    }

    public function summary(): CartSummary
    {
        $cart = $this->current();
        $currency = $this->currency->current();

        if ($cart === null) {
            return new CartSummary([], $currency);
        }

        $cart->load('items.product.prices');
        $lines = [];

        foreach ($cart->items as $item) {
            $product = $item->product;
            $unitPrice = $product->priceIn($currency);

            $lines[] = new CartLine(
                item: $item,
                product: $product,
                quantity: $item->quantity,
                unitPrice: $unitPrice,
                isAvailable: $unitPrice !== null && $product->isAvailable($item->quantity),
            );
        }

        return new CartSummary($lines, $currency);
    }

    public function itemCount(): int
    {
        $cart = $this->current();

        return $cart === null ? 0 : (int) $cart->items()->sum('quantity');
    }

    private function currentOrCreate(): Cart
    {
        if ($cart = $this->current()) {
            return $cart;
        }

        $this->cart = Cart::query()->create([
            'token' => (string) Str::uuid(),
            'currency' => $this->currency->current(),
        ]);

        $this->session->put(self::SESSION_KEY, $this->cart->token);
        $this->resolvedToken = $this->cart->token;

        return $this->cart;
    }

    private function findItem(int $itemId): CartItem
    {
        $item = $this->current()?->items()->with('product')->find($itemId);

        if ($item === null) {
            throw new CartException(__('cart::messages.item_not_found'));
        }

        return $item;
    }

    private function ensurePurchasable(Product $product): void
    {
        if (! $product->is_visible || $product->priceIn($this->currency->current()) === null) {
            throw new CartException(__('cart::messages.unavailable', ['product' => $product->name]));
        }
    }

    private function ensureStock(Product $product, int $quantity): void
    {
        $max = $product->maxPurchasableQuantity();

        if ($max !== null && $quantity > $max) {
            throw new CartException($max === 0
                ? __('cart::messages.sold_out', ['product' => $product->name])
                : __('cart::messages.not_enough_stock', ['product' => $product->name, 'available' => $max]));
        }
    }
}
