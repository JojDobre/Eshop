<?php

namespace Tests\Feature\Shop;

use App\Core\Support\Currencies;
use App\Core\Support\EcbRates;
use App\Filament\Pages\Settings\ManageCurrencySettings;
use App\Settings\CurrencySettings;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Orders\Models\Coupon;
use Modules\Orders\Models\Order;
use Modules\Payments\PaymentType;

class CurrenciesTest extends ShopTestCase
{
    private function enableCzk(string $rate = '25', string $rounding = Currencies::ROUND_WHOLE): void
    {
        $settings = app(CurrencySettings::class);
        $settings->enabled = ['CZK'];
        $settings->rates = ['CZK' => $rate];
        $settings->rounding = ['CZK' => $rounding];
        $settings->save();
        app(Currencies::class)->refresh();
    }

    public function test_conversion_and_rounding(): void
    {
        $this->enableCzk('25.2', Currencies::ROUND_MATH);
        $currencies = app(Currencies::class);

        $this->assertSame(['EUR', 'CZK'], $currencies->enabled());
        $this->assertSame(32508, $currencies->convert(1290, 'EUR', 'CZK'));
        $this->assertSame(1290, $currencies->convert(32508, 'CZK', 'EUR'));

        $this->enableCzk('25.2', Currencies::ROUND_WHOLE);
        $this->assertSame(32500, app(Currencies::class)->convert(1290, 'EUR', 'CZK'));

        $this->enableCzk('25.2', Currencies::ROUND_NINETY_NINE);
        $this->assertSame(32599, app(Currencies::class)->convert(1290, 'EUR', 'CZK'));
    }

    public function test_currency_without_rate_is_not_offered(): void
    {
        $settings = app(CurrencySettings::class);
        $settings->enabled = ['CZK', 'USD'];
        $settings->rates = ['CZK' => '25'];
        $settings->save();
        app(Currencies::class)->refresh();

        $this->assertSame(['EUR', 'CZK'], app(Currencies::class)->enabled());
    }

    public function test_product_price_converted_or_manual(): void
    {
        $this->enableCzk();
        $product = $this->product('Etiópia', '12.90');

        $this->assertSame(32300, $product->priceIn('CZK'));

        $product->setPrice('CZK', 29900);
        $this->assertSame(29900, $product->fresh()->priceIn('CZK'));
    }

    public function test_customer_switches_currency_and_orders_in_czk(): void
    {
        $this->enableCzk();
        $product = $this->product('Etiópia', '12.90');

        $this->get($product->url())->assertSee("12,90\u{00A0}€")->assertSee('name="currency" value="CZK"', false);

        $this->post(route('storefront.currency'), ['currency' => 'CZK'])->assertRedirect();
        $this->get($product->url())->assertSee("323,00\u{00A0}CZK");
        $this->post(route('storefront.currency'), ['currency' => 'XXX']);
        $this->get($product->url())->assertSee("323,00\u{00A0}CZK");

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData(['payment_method_id' => $this->payment(PaymentType::CashOnDelivery)->id]));

        $order = Order::query()->firstOrFail();
        $this->assertSame('CZK', $order->currency);
        $this->assertSame(64600, $order->subtotal);
        $this->assertSame('25.00000000', (string) $order->exchange_rate);
        // Doprava 4,90 € a dobierka 1,50 € prepočítané kurzom (zaokrúhlené na celé koruny).
        $this->assertSame(12300, $order->shipping_price);
        $this->assertSame(3800, $order->payment_fee);
        $this->assertSame(64600 + 12300 + 3800, $order->total);

        // Administrácia ostáva v predvolenej mene.
        $this->actingAs($this->superAdmin());
        $this->get('/admin')->assertOk();
    }

    public function test_manual_shipping_price_and_fixed_coupon_in_other_currency(): void
    {
        $this->enableCzk();
        $courier = $this->courier();
        $courier->update(['currency_prices' => ['CZK' => ['price' => 9900, 'free_from' => 100000]]]);
        Coupon::query()->create(['code' => 'ZLAVA5', 'type' => 'fixed', 'value' => 500, 'currency' => 'EUR', 'is_active' => true]);
        $product = $this->product('Etiópia', '12.90');

        $this->post(route('storefront.currency'), ['currency' => 'CZK']);
        $this->post(route('cart.add'), ['product_id' => $product->id]);
        $this->post(route('coupon.apply'), ['code' => 'ZLAVA5'])->assertSessionHasNoErrors();
        $this->post(route('checkout.store'), $this->checkoutData(['payment_method_id' => $this->payment(PaymentType::BankTransfer)->id]));

        $order = Order::query()->firstOrFail();
        $this->assertSame(9900, $order->shipping_price);
        $this->assertSame(12500, $order->discount);
    }

    public function test_ecb_rates_update(): void
    {
        $settings = app(CurrencySettings::class);
        $settings->enabled = ['CZK', 'USD'];
        $settings->save();

        Http::fake([EcbRates::URL => Http::response('<?xml version="1.0"?><gesmes:Envelope xmlns:gesmes="http://www.gesmes.org/xml/2002-08-01" xmlns="http://www.ecb.int/vocabulary/2002-08-01/eurofxref"><Cube><Cube time="2026-11-06"><Cube currency="USD" rate="1.1165"/><Cube currency="CZK" rate="24.350"/></Cube></Cube></gesmes:Envelope>')]);

        $rates = app(EcbRates::class)->update();

        $this->assertSame('24.35', $rates['CZK']);
        $this->assertSame('1.1165', $rates['USD']);
        $this->assertNotNull(app(CurrencySettings::class)->rates_updated_at);
        $this->assertSame(['EUR', 'CZK', 'USD'], app(Currencies::class)->enabled());
    }

    public function test_admin_currency_settings_and_product_prices(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/currencies')->assertOk();

        Livewire::test(ManageCurrencySettings::class)
            ->fillForm(['enabled' => ['CZK'], 'rates' => ['CZK' => '25'], 'rounding' => ['CZK' => Currencies::ROUND_WHOLE], 'auto_update' => false])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(['EUR', 'CZK'], app(Currencies::class)->enabled());

        $product = $this->product('Mlynček', '40.00');
        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->fillForm(['other_prices' => ['CZK' => ['price' => '999', 'compare_at' => '']]])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame(99900, $product->fresh()->priceIn('CZK'));

        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->assertFormSet(['other_prices.CZK.price' => '999.00'])
            ->fillForm(['other_prices' => ['CZK' => ['price' => '', 'compare_at' => '']]])
            ->call('save');
        $this->assertSame(100000, $product->fresh()->priceIn('CZK'));
    }
}
