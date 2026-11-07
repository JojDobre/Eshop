<?php

namespace Tests\Feature\Shop;

use App\Settings\TaxSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Mail\LowStockNotification;
use Modules\Catalog\Models\Manufacturer;
use Modules\Catalog\Models\StockMovement;
use Modules\Orders\Actions\ChangeOrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Tax\Models\TaxRate;

class TaxAndStockTest extends ShopTestCase
{
    public function test_default_slovak_tax_rates_are_created(): void
    {
        $this->assertSame(['23.00', '19.00', '5.00'], TaxRate::query()->orderBy('position')->pluck('rate')->all());
        $this->assertTrue(TaxRate::default()->is_default);
    }

    public function test_prices_entered_without_vat_are_shown_with_vat(): void
    {
        $settings = app(TaxSettings::class);
        $settings->prices_include_vat = false;
        $settings->save();

        $reduced = TaxRate::query()->where('rate', 19)->firstOrFail();
        $product = $this->product('Kniha', '10.00', attributes: ['tax_rate_id' => $reduced->id]);

        $this->assertSame(1190, $product->priceIn('EUR'));
        $this->get($product->url())->assertSee("11,90\u{00A0}€");
    }

    public function test_order_stores_vat_rates_and_breakdown(): void
    {
        $reduced = TaxRate::query()->where('rate', 5)->firstOrFail();
        $book = $this->product('Kniha', '10.50', attributes: ['tax_rate_id' => $reduced->id]);
        $mug = $this->product('Hrnček', '12.30');

        $this->post(route('cart.add'), ['product_id' => $book->id]);
        $this->post(route('cart.add'), ['product_id' => $mug->id]);
        $this->post(route('checkout.store'), $this->checkoutData());

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertSame(5.0, $order->items->firstWhere('name', 'Kniha')->tax_rate);
        $this->assertSame(23.0, $order->shipping_tax_rate);

        $breakdown = $order->vatBreakdown();
        $this->assertSame(1230 + 490, $breakdown['23.00']['gross']);
        $this->assertSame(1050, $breakdown['5.00']['gross']);
        $this->assertSame(1000, $breakdown['5.00']['net']);
    }

    public function test_non_vat_payer_orders_have_zero_rates(): void
    {
        $settings = app(TaxSettings::class);
        $settings->vat_payer = false;
        $settings->save();

        $this->post(route('cart.add'), ['product_id' => $this->product('Hrnček')->id]);
        $this->post(route('checkout.store'), $this->checkoutData());

        $order = Order::query()->with('items')->firstOrFail();
        $this->assertFalse($order->vat_payer);
        $this->assertSame(0.0, $order->items->first()->tax_rate);
    }

    public function test_discount_shows_original_and_lowest_price_in_30_days(): void
    {
        $product = $this->product('Mlynček', '49.00');

        // Pred 10 dňami stál 45 €, dnes je v zľave za 39 € (pôvodne 59 €).
        DB::table('product_price_history')->where('product_id', $product->id)->update(['recorded_at' => now()->subDays(40)]);
        $this->travelTo(now()->subDays(10), fn () => $product->setPrice('EUR', 4500));
        $product->setPrice('EUR', 3900, 5900);

        $this->get($product->fresh(['prices'])->url())
            ->assertSee("39,00\u{00A0}€")
            ->assertSee("59,00\u{00A0}€")
            ->assertSee('−34 %')
            ->assertSee("Najnižšia cena za posledných 30 dní: 39,00\u{00A0}€");
    }

    public function test_lowest_price_uses_price_valid_at_start_of_period(): void
    {
        $product = $this->product('Mlynček', '30.00');
        DB::table('product_price_history')->where('product_id', $product->id)->update(['recorded_at' => now()->subDays(60)]);
        $product->setPrice('EUR', 4000, 5000);

        // Cena 30 € platila ešte na začiatku 30-dňového obdobia.
        $this->assertSame(3000, $product->fresh(['prices'])->lowestPriceIn30Days('EUR'));
    }

    public function test_manufacturer_safety_information_is_shown_on_product_page(): void
    {
        $manufacturer = Manufacturer::query()->create([
            'name' => 'Kávovar s.r.o.', 'address' => 'Hlavná 1, Bratislava', 'email' => 'info@kavovar.sk',
            'eu_responsible_person' => 'EU Rep GmbH, Wien', 'safety_info' => 'Neodporúča sa deťom do 3 rokov.',
        ]);
        $product = $this->product('Mlynček', attributes: ['manufacturer_id' => $manufacturer->id]);

        $this->get($product->url())
            ->assertSee('Bezpečnosť produktu')
            ->assertSee('Kávovar s.r.o.')
            ->assertSee('EU Rep GmbH, Wien')
            ->assertSee('Neodporúča sa deťom do 3 rokov.');
    }

    public function test_stock_movements_are_recorded_for_orders_cancellations_and_edits(): void
    {
        $product = $this->product('Etiópia', stock: 10);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => 3]);
        $this->post(route('checkout.store'), $this->checkoutData());
        $order = Order::query()->firstOrFail();

        app(ChangeOrderStatus::class)->handle($order, OrderStatus::Cancelled);
        $product->refresh()->update(['stock_quantity' => 15]);

        $movements = StockMovement::query()->where('product_id', $product->id)->orderBy('id')->get(['type', 'quantity', 'stock_after', 'reference']);

        $this->assertSame([
            [StockMovement::ORDER, -3, 7, $order->number],
            [StockMovement::CANCEL, 3, 10, $order->number],
            [StockMovement::EDIT, 5, 15, null],
        ], $movements->map(fn ($m) => [$m->type, $m->quantity, $m->stock_after, $m->reference])->all());
    }

    public function test_manual_stock_adjustment_in_admin(): void
    {
        $product = $this->product('Etiópia', stock: 4);
        $this->actingAs($admin = $this->superAdmin());

        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->callAction('adjustStock', ['quantity' => 6, 'note' => 'Príjem od dodávateľa']);

        $this->assertSame(10, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'manual', 'quantity' => 6, 'note' => 'Príjem od dodávateľa', 'user_id' => $admin->id]);
    }

    public function test_merchant_is_notified_once_when_stock_gets_low(): void
    {
        Mail::fake();
        $product = $this->product('Etiópia', stock: 5);

        foreach ([2, 1] as $quantity) {
            $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => $quantity]);
            $this->post(route('checkout.store'), $this->checkoutData());
        }

        // 5 → 3 (prekročená hranica 3: upozornenie), 3 → 2 (už nie).
        Mail::assertSent(LowStockNotification::class, 1);
    }

    public function test_new_admin_pages_open(): void
    {
        $this->actingAs($this->superAdmin());

        foreach (['/admin/settings/tax', '/admin/settings/tax-rates', '/admin/settings/catalog', '/admin/manufacturers'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
