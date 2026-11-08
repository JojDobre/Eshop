<?php

namespace Tests\Feature\Shop;

use Livewire\Livewire;
use Modules\Orders\CouponType;
use Modules\Orders\Filament\Resources\Coupons\Pages\ManageCoupons;
use Modules\Orders\Models\Coupon;
use Modules\Orders\Models\Order;
use Modules\Tax\Models\TaxRate;

class CouponsAndShippingTest extends ShopTestCase
{
    private function coupon(array $attributes): Coupon
    {
        return Coupon::query()->create(['code' => 'leto10', 'type' => CouponType::Percent, 'value' => 10, ...$attributes]);
    }

    public function test_percent_coupon_reduces_goods_and_is_counted(): void
    {
        $coupon = $this->coupon([]);
        $product = $this->product('Mlynček', '50.00');
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $this->post(route('coupon.apply'), ['code' => ' Leto10 '])->assertSessionHas('success', 'Zľavový kód LETO10 bol uplatnený.');
        $this->get(route('cart.show'))->assertSee('Zľava (LETO10)')->assertSee("−5,00\u{00A0}€");
        $this->get(route('checkout.show'))->assertSee("−5,00\u{00A0}€");

        $this->post(route('checkout.store'), $this->checkoutData());

        $order = Order::query()->firstOrFail();
        $this->assertSame('LETO10', $order->coupon_code);
        $this->assertSame(500, $order->discount);
        // 50 € − 5 € = 45 € < 60 € → doprava 4,90 €.
        $this->assertSame(5000 - 500 + 490, $order->total);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_fixed_and_free_shipping_coupons(): void
    {
        $product = $this->product('Mlynček', '20.00');
        $this->coupon(['code' => 'MINUS30', 'type' => CouponType::Fixed, 'value' => 3000]);
        $this->coupon(['code' => 'DOPRAVA', 'type' => CouponType::FreeShipping, 'value' => 0]);

        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('coupon.apply'), ['code' => 'minus30']);
        $this->post(route('checkout.store'), $this->checkoutData());

        // Zľava nikdy neprevýši hodnotu tovaru.
        $first = Order::query()->firstOrFail();
        $this->assertSame(2000, $first->discount);
        $this->assertSame(490, $first->total);

        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('coupon.apply'), ['code' => 'doprava']);
        $this->post(route('checkout.store'), $this->checkoutData());

        $second = Order::query()->latest('id')->firstOrFail();
        $this->assertSame(0, $second->shipping_price);
        $this->assertSame(2000, $second->total);
    }

    public function test_invalid_expired_used_up_and_minimum_order_coupons_are_rejected(): void
    {
        $product = $this->product('Hrnček', '10.00');
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $this->coupon(['code' => 'STARY', 'ends_at' => now()->subDay()]);
        $this->coupon(['code' => 'MINULY', 'usage_limit' => 1, 'used_count' => 1]);
        $this->coupon(['code' => 'VELKY', 'min_order_amount' => 5000]);
        $this->coupon(['code' => 'VYPNUTY', 'is_active' => false]);

        $this->post(route('coupon.apply'), ['code' => 'NEEXISTUJE'])->assertSessionHas('error', 'Tento zľavový kód neplatí.');
        $this->post(route('coupon.apply'), ['code' => 'STARY'])->assertSessionHas('error', 'Platnosť zľavového kódu vypršala.');
        $this->post(route('coupon.apply'), ['code' => 'MINULY'])->assertSessionHas('error', 'Zľavový kód už bol vyčerpaný.');
        $this->post(route('coupon.apply'), ['code' => 'VELKY'])->assertSessionHas('error', "Zľavový kód platí pri nákupe od 50,00\u{00A0}€.");
        $this->post(route('coupon.apply'), ['code' => 'VYPNUTY'])->assertSessionHas('error', 'Tento zľavový kód neplatí.');
    }

    public function test_coupon_that_became_invalid_before_ordering_stops_the_order(): void
    {
        $coupon = $this->coupon(['usage_limit' => 1]);
        $product = $this->product('Hrnček', '10.00');
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('coupon.apply'), ['code' => 'LETO10']);

        // Medzitým kupón použil niekto iný.
        $coupon->update(['used_count' => 1]);

        // Zákazník nezaplatí inú sumu, než akú videl: objednávka sa zastaví s vysvetlením.
        $this->post(route('checkout.store'), $this->checkoutData())
            ->assertRedirect(route('cart.show'))
            ->assertSessionHas('error', 'Zľavový kód už bol vyčerpaný.');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_discount_is_split_across_vat_rates(): void
    {
        $reduced = TaxRate::query()->where('rate', 5)->firstOrFail();
        $this->post(route('cart.add'), ['product_id' => $this->product('Kniha', '30.00', attributes: ['tax_rate_id' => $reduced->id])->id]);
        $this->post(route('cart.add'), ['product_id' => $this->product('Hrnček', '10.00')->id]);
        $this->coupon(['code' => 'POLOVICA', 'value' => 50]);
        $this->post(route('coupon.apply'), ['code' => 'POLOVICA']);
        $this->post(route('checkout.store'), $this->checkoutData());

        $breakdown = Order::query()->with('items')->firstOrFail()->vatBreakdown();
        $this->assertSame(1500, $breakdown['5.00']['gross']);
        $this->assertSame(500 + 490, $breakdown['23.00']['gross']);
    }

    public function test_shipping_price_by_weight_and_unavailable_when_too_heavy(): void
    {
        $courier = $this->courier();
        $courier->update(['free_from' => null, 'weight_rates' => [['up_to' => 2000, 'price' => 390], ['up_to' => 10000, 'price' => 690]]]);
        $light = $this->product('Káva', '10.00', attributes: ['weight' => 500]);
        $heavy = $this->product('Mlynček', '10.00', attributes: ['weight' => 3000]);

        $this->post(route('cart.add'), ['product_id' => $light->id, 'quantity' => 2]);
        $this->get(route('checkout.show'))->assertSee("3,90\u{00A0}€");

        $this->post(route('cart.add'), ['product_id' => $heavy->id]);
        $this->get(route('checkout.show'))->assertSee("6,90\u{00A0}€");

        $this->post(route('cart.add'), ['product_id' => $heavy->id, 'quantity' => 3]);
        // 1 kg + 12 kg = 13 kg > 10 kg → kuriér sa neponúkne, osobný odber áno.
        $this->get(route('checkout.show'))->assertDontSee('Kuriér na adresu')->assertSee('Osobný odber');
        $this->post(route('checkout.store'), $this->checkoutData())->assertSessionHasErrors('shipping_method_id');
    }

    public function test_coupons_are_managed_in_admin(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageCoupons::class)
            ->callAction('create', ['code' => 'vianoce', 'type' => 'fixed', 'value' => '5,50', 'is_active' => true])
            ->assertHasNoActionErrors();

        $coupon = Coupon::query()->firstOrFail();
        $this->assertSame('VIANOCE', $coupon->code);
        $this->assertSame(550, $coupon->value);
        $this->get('/admin/coupons')->assertOk()->assertSee('VIANOCE');
    }
}
