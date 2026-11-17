<?php

namespace Tests\Feature\Shop;

use Modules\Cart\CartManager;
use Modules\Orders\Models\Order;

class MinimumQuantityTest extends ShopTestCase
{
    public function test_category_minimum_combines_kinds_and_ignores_other_products(): void
    {
        $eggs = $this->category('Násadové vajcia');
        $eggs->update(['min_quantity' => 10]);
        $quail = $this->category('Prepelice', $eggs);
        $white = $this->product('Biele vajcia', '1.00', 100, $quail);
        $brown = $this->product('Hnedé vajcia', '1.00', 100, $eggs);
        $cage = $this->product('Klietka', '50.00', 5);

        $cart = app(CartManager::class);
        $cart->add($white, 3);
        $cart->add($cage, 1);

        // 3 vajcia + klietka (nepočíta sa) – chýba 7 ks.
        $summary = $cart->summary();
        $this->assertFalse($summary->canCheckout());
        $this->assertSame([['name' => 'Násadové vajcia', 'minimum' => 10, 'quantity' => 3, 'missing' => 7, 'type' => 'category']], $summary->minimumViolations());

        $this->get(route('cart.show'))->assertSee('Pridajte ešte 7 ks', false)->assertDontSee(route('checkout.show'));
        $this->get(route('checkout.show'))->assertRedirect(route('cart.show'));
        $this->post(route('checkout.store'), $this->checkoutData())->assertRedirect(route('cart.show'));
        $this->assertSame(0, Order::query()->count());

        // 3 + 7 iného druhu = 10 ks.
        $cart->add($brown, 7);
        $this->assertTrue($cart->summary()->canCheckout());
        $this->post(route('checkout.store'), $this->checkoutData());
        $this->assertSame(1, Order::query()->count());
    }

    public function test_cart_with_only_excluded_products_has_no_minimum(): void
    {
        $eggs = $this->category('Násadové vajcia');
        $eggs->update(['min_quantity' => 10]);
        $this->product('Biele vajcia', '1.00', 100, $eggs);
        $cage = $this->product('Klietka', '50.00', 5);

        app(CartManager::class)->add($cage, 1);

        $this->assertTrue(app(CartManager::class)->summary()->canCheckout());
    }

    public function test_product_minimum_and_hints_on_product_page(): void
    {
        $product = $this->product('Kŕmna zmes', '2.00', 100, attributes: ['min_quantity' => 5]);
        $eggs = $this->category('Násadové vajcia');
        $eggs->update(['min_quantity' => 10]);
        $egg = $this->product('Biele vajcia', '1.00', 100, $eggs);

        $this->get($product->url())->assertSee('Minimálne množstvo na objednávku: 5 ks.')->assertSee('value="5"', false);
        $this->get($egg->url())->assertSee('10 ks spolu z kategórie Násadové vajcia');

        $cart = app(CartManager::class);
        $cart->add($product, 4);
        $this->assertSame(1, $cart->summary()->minimumViolations()[0]['missing']);

        $cart->add($product, 1);
        $this->assertTrue($cart->summary()->canCheckout());
    }
}
