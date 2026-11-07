<?php

namespace Modules\Cart;

use App\Core\Support\CurrencyContext;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Cart\Models\Cart;
use Modules\Cart\Models\CartItem;
use Modules\Catalog\Models\Addon;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;

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

    /**
     * @param  array<int|string, string|null>  $addons  Zvolené doplnkové voľby: id => text (alebo null).
     */
    public function add(Product $product, int $quantity = 1, ?ProductVariant $variant = null, array $addons = []): CartItem
    {
        $quantity = max(1, $quantity);
        $addons = $this->normalizeAddons($product, $addons);

        if ($product->hasVariants() && $variant === null) {
            throw new CartException(__('cart::messages.choose_variant', ['product' => $product->name]));
        }

        if ($variant !== null && ($variant->product_id !== $product->id || ! $variant->is_active)) {
            throw new CartException(__('cart::messages.unavailable', ['product' => $product->name]));
        }

        $variant?->setRelation('product', $product);
        $this->ensurePurchasable($product, $variant);

        $cart = $this->currentOrCreate();
        // Rovnaký produkt s inými doplnkovými voľbami je samostatná položka.
        $hash = $addons === [] ? null : sha1((string) json_encode($addons));
        $item = $cart->items()->firstOrNew(
            ['product_id' => $product->id, 'product_variant_id' => $variant?->id, 'addons_hash' => $hash],
            ['quantity' => 0, 'addons' => $addons === [] ? null : $addons],
        );
        $newQuantity = min(self::MAX_QUANTITY, $item->quantity + $quantity);

        $this->ensureStock($product, $newQuantity, $variant);

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
        $item->variant?->setRelation('product', $item->product);
        $this->ensureStock($item->product, $quantity, $item->variant);

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

        $cart->load('items.product.prices', 'items.product.taxRate', 'items.variant.prices', 'items.variant.values');
        $lines = [];
        $addonIds = $cart->items->flatMap(fn (CartItem $item) => array_keys((array) $item->addons))->unique()->all();
        $addonModels = $addonIds === [] ? collect() : Addon::query()->whereIn('id', $addonIds)->where('is_active', true)->get()->keyBy('id');

        foreach ($cart->items as $item) {
            $product = $item->product;
            $variant = $item->variant?->setRelation('product', $product);
            $unitPrice = $variant ? $variant->priceIn($currency) : $product->priceIn($currency);
            $addons = $this->lineAddons($item, $addonModels, $currency);

            if ($unitPrice !== null) {
                $unitPrice += array_sum(array_column($addons, 'price'));
            }

            // Produkt, ktorý medzitým dostal varianty, sa musí vybrať znova.
            $available = $variant
                ? $variant->isAvailable($item->quantity)
                : ! $product->hasVariants() && $product->isAvailable($item->quantity);

            $lines[] = new CartLine(
                item: $item,
                product: $product,
                quantity: $item->quantity,
                unitPrice: $unitPrice,
                isAvailable: $unitPrice !== null && $available,
                variant: $variant,
                addons: $addons,
            );
        }

        return new CartSummary($lines, $currency);
    }

    /**
     * Zľavový kód uložený pri košíku (overuje ho modul Objednávky).
     */
    public function couponCode(): ?string
    {
        return $this->current()?->coupon_code;
    }

    public function setCouponCode(?string $code): void
    {
        $cart = $code === null ? $this->current() : $this->currentOrCreate();
        $cart?->update(['coupon_code' => $code]);
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
        $item = $this->current()?->items()->with(['product', 'variant.values'])->find($itemId);

        if ($item === null) {
            throw new CartException(__('cart::messages.item_not_found'));
        }

        return $item;
    }

    private function ensurePurchasable(Product $product, ?ProductVariant $variant = null): void
    {
        $price = $variant ? $variant->priceIn($this->currency->current()) : $product->priceIn($this->currency->current());

        if (! $product->is_visible || $price === null) {
            throw new CartException(__('cart::messages.unavailable', ['product' => $product->name]));
        }
    }

    private function ensureStock(Product $product, int $quantity, ?ProductVariant $variant = null): void
    {
        $max = $variant ? $variant->maxPurchasableQuantity() : $product->maxPurchasableQuantity();
        $name = $variant ? $product->name.' ('.$variant->name().')' : $product->name;

        if ($max !== null && $quantity > $max) {
            throw new CartException($max === 0
                ? __('cart::messages.sold_out', ['product' => $name])
                : __('cart::messages.not_enough_stock', ['product' => $name, 'available' => $max]));
        }
    }

    /**
     * Overí zvolené doplnkové voľby (patria k produktu, povinný text).
     *
     * @param  array<int|string, string|null>  $addons
     * @return array<int, string|null> id => text, zoradené podľa id
     */
    private function normalizeAddons(Product $product, array $addons): array
    {
        if ($addons === []) {
            return [];
        }

        $available = $product->availableAddons()->keyBy('id');
        $normalized = [];

        foreach ($addons as $id => $text) {
            $addon = $available->get((int) $id);

            if ($addon === null) {
                throw new CartException(__('cart::messages.addon_unavailable'));
            }

            $text = $addon->needs_text ? mb_substr(trim((string) $text), 0, $addon->text_max_length) : null;

            if ($addon->needs_text && $text === '') {
                throw new CartException(__('cart::messages.addon_text_required', ['addon' => $addon->name]));
            }

            $normalized[(int) $id] = $text;
        }

        ksort($normalized);

        return $normalized;
    }

    /**
     * @param  Collection<int, Addon>  $models
     * @return list<array{id: int, name: string, price: int, text: string|null}>
     */
    private function lineAddons(CartItem $item, Collection $models, string $currency): array
    {
        $addons = [];

        foreach ((array) $item->addons as $id => $text) {
            if ($addon = $models->get((int) $id)) {
                $addons[] = ['id' => $addon->id, 'name' => $addon->name, 'price' => $addon->priceIn($currency), 'text' => $text];
            }
        }

        return $addons;
    }
}
