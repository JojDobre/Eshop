<?php

namespace Tests\Feature\Shop;

use Modules\Cart\Models\CartItem;

class CartTest extends ShopTestCase
{
    public function test_product_can_be_added_updated_and_removed(): void
    {
        $product = $this->product('Etiópia', '12.90', 10);

        $this->from($product->url())->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect($product->url())
            ->assertSessionHas('cart_added', 'Etiópia');

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 1]);

        $this->get(route('cart.show'))->assertOk()->assertSee('Etiópia')->assertSee("38,70\u{00A0}€");

        $item = CartItem::query()->firstOrFail();
        $this->assertSame(3, $item->quantity);

        $this->patch(route('cart.update', $item->id), ['quantity' => 5])->assertRedirect(route('cart.show'));
        $this->assertSame(5, $item->fresh()->quantity);

        $this->delete(route('cart.remove', $item->id))->assertRedirect(route('cart.show'));
        $this->assertDatabaseCount('cart_items', 0);
        $this->get(route('cart.show'))->assertSee('Váš košík je prázdny.');
    }

    public function test_quantity_is_limited_by_stock(): void
    {
        $product = $this->product('Brazília', stock: 2);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3])
            ->assertSessionHas('error', 'Produktu „Brazília" máme na sklade iba 2 ks.');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_hidden_or_sold_out_products_cannot_be_added(): void
    {
        $hidden = $this->product('Skrytý', attributes: ['is_visible' => false]);
        $soldOut = $this->product('Vypredaný', stock: 0);

        $this->post(route('cart.add'), ['product_id' => $hidden->id])->assertSessionHas('error');
        $this->post(route('cart.add'), ['product_id' => $soldOut->id])->assertSessionHas('error', 'Produkt „Vypredaný" je vypredaný.');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_products_without_stock_tracking_can_be_bought_in_any_quantity(): void
    {
        $service = $this->product('Konzultácia', stock: 0, attributes: ['track_stock' => false]);

        $this->post(route('cart.add'), ['product_id' => $service->id, 'quantity' => 50])->assertSessionHas('cart_added');
    }

    public function test_another_visitor_cannot_change_foreign_cart_items(): void
    {
        $product = $this->product('Etiópia');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $item = CartItem::query()->firstOrFail();

        $this->flushSession();

        $this->delete(route('cart.remove', $item->id))->assertSessionHas('error');
        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    public function test_cart_shows_free_shipping_progress(): void
    {
        $product = $this->product('Mlynček', '49.00');
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $this->get(route('cart.show'))->assertSee("Do dopravy zadarmo vám chýba 11,00\u{00A0}€.");
    }

    public function test_header_shows_cart_count(): void
    {
        $product = $this->product('Etiópia');
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3]);

        $this->get('/')->assertSee('Košík, 3 kusy');
    }
}
