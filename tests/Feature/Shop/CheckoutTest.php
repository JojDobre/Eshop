<?php

namespace Tests\Feature\Shop;

use Illuminate\Support\Facades\Mail;
use Modules\Orders\Mail\NewOrderNotification;
use Modules\Orders\Mail\OrderConfirmation;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;
use Modules\Payments\PaymentType;

class CheckoutTest extends ShopTestCase
{
    public function test_empty_cart_redirects_to_cart(): void
    {
        $this->get(route('checkout.show'))->assertRedirect(route('cart.show'));
    }

    public function test_customer_can_place_an_order(): void
    {
        Mail::fake();
        $product = $this->product('Etiópia', '12.90', 10);
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);

        $this->get(route('checkout.show'))->assertOk()->assertSee('Kuriér na adresu')->assertSee('obchodnými podmienkami');

        $response = $this->post(route('checkout.store'), $this->checkoutData());

        $order = Order::query()->with('items')->firstOrFail();
        $response->assertRedirect($order->publicUrl());

        $this->assertSame(date('Y').'00001', $order->number);
        $this->assertSame(OrderStatus::New, $order->status);
        $this->assertSame(PaymentStatus::Unpaid, $order->payment_status);
        $this->assertSame('jana@example.com', $order->email);
        $this->assertSame(2580, $order->subtotal);
        $this->assertSame(490, $order->shipping_price);
        $this->assertSame(0, $order->payment_fee);
        $this->assertSame(3070, $order->total);
        $this->assertSame('Hlavná 1', $order->shipping_street);
        $this->assertSame('Etiópia', $order->items->first()->name);
        $this->assertNotNull($order->terms_accepted_at);

        // Sklad odpísaný, košík prázdny.
        $this->assertSame(8, $product->fresh()->stock_quantity);
        $this->assertDatabaseCount('carts', 0);

        Mail::assertSent(OrderConfirmation::class, fn ($mail) => $mail->hasTo('jana@example.com'));
        Mail::assertSent(NewOrderNotification::class, fn ($mail) => $mail->hasTo('info@example.com'));

        $this->get($order->publicUrl())->assertOk()
            ->assertSee('Ďakujeme za objednávku')
            ->assertSee($order->variableSymbol())
            ->assertSee("30,70\u{00A0}€");
    }

    public function test_order_emails_render(): void
    {
        $product = $this->product('Etiópia', '12.90');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData());
        $order = Order::query()->firstOrFail();

        $confirmation = (new OrderConfirmation($order))->render();
        $this->assertStringContainsString($order->number, $confirmation);
        $this->assertStringContainsString('Variabilný symbol', $confirmation);

        $this->assertStringContainsString('Jana Nováková', (new NewOrderNotification($order))->render());
    }

    public function test_free_shipping_above_threshold(): void
    {
        $product = $this->product('Mlynček', '60.00');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData());

        $this->assertSame(0, Order::query()->firstOrFail()->shipping_price);
    }

    public function test_cash_on_delivery_fee_is_added(): void
    {
        $product = $this->product('Etiópia', '10.00');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData([
            'payment_method_id' => $this->payment(PaymentType::CashOnDelivery)->id,
        ]));

        $order = Order::query()->firstOrFail();
        $this->assertSame(150, $order->payment_fee);
        $this->assertSame(1000 + 490 + 150, $order->total);
    }

    public function test_payment_not_allowed_for_shipping_is_rejected(): void
    {
        $product = $this->product('Etiópia');
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        // Platba na predajni je povolená iba pri osobnom odbere.
        $this->post(route('checkout.store'), $this->checkoutData([
            'payment_method_id' => $this->payment(PaymentType::Cash)->id,
        ]))->assertSessionHasErrors('payment_method_id');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_pickup_order_has_no_shipping_address(): void
    {
        $product = $this->product('Etiópia');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('checkout.store'), $this->checkoutData([
            'shipping_method_id' => $this->pickup()->id,
            'payment_method_id' => $this->payment(PaymentType::Cash)->id,
        ]));

        $order = Order::query()->firstOrFail();
        $this->assertNull($order->shipping_street);
        $this->assertSame(0, $order->shipping_price);
    }

    public function test_different_shipping_address_and_company_details(): void
    {
        $product = $this->product('Etiópia');
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $this->post(route('checkout.store'), $this->checkoutData(['is_company' => '1', 'ship_to_different_address' => '1']))
            ->assertSessionHasErrors(['billing_company', 'billing_company_id', 'shipping_name', 'shipping_street']);

        $this->post(route('checkout.store'), $this->checkoutData([
            'is_company' => '1', 'billing_company' => 'Jana s.r.o.', 'billing_company_id' => '12345678',
            'ship_to_different_address' => '1', 'shipping_name' => 'Peter', 'shipping_street' => 'Dlhá 5',
            'shipping_city' => 'Košice', 'shipping_zip' => '04001', 'shipping_country' => 'SK',
        ]));

        $order = Order::query()->firstOrFail();
        $this->assertSame('Jana s.r.o.', $order->billing_company);
        $this->assertSame('Dlhá 5', $order->shipping_street);
        $this->assertSame('Košice', $order->shipping_city);
    }

    public function test_validation_and_terms_are_required(): void
    {
        $product = $this->product('Etiópia');
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $this->post(route('checkout.store'), $this->checkoutData(['email' => 'nie-je-email', 'terms' => null]))
            ->assertSessionHasErrors(['email', 'terms']);
    }

    public function test_order_fails_when_stock_ran_out_meanwhile(): void
    {
        $product = $this->product('Posledný kus', stock: 1);
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        // Medzitým kúpil posledný kus niekto iný.
        $product->update(['stock_quantity' => 0]);

        $this->post(route('checkout.store'), $this->checkoutData())->assertRedirect(route('cart.show'));
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_order_numbers_are_sequential(): void
    {
        $product = $this->product('Etiópia');

        foreach ([1, 2] as $_) {
            $this->post(route('cart.add'), ['product_id' => $product->id]);
            $this->post(route('checkout.store'), $this->checkoutData());
        }

        $this->assertSame([date('Y').'00001', date('Y').'00002'], Order::query()->orderBy('id')->pluck('number')->all());
    }

    public function test_order_page_requires_valid_token(): void
    {
        $this->get('/objednavka/'.str_repeat('a', 48))->assertNotFound();
    }
}
