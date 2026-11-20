<?php

namespace Tests\Feature\Shop;

use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Orders\Filament\Widgets\SalesChart;
use Modules\Orders\Filament\Widgets\TopProducts;
use Modules\Orders\Filament\Widgets\UnpaidOrders;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\Reports\SalesReport;

class SalesReportTest extends ShopTestCase
{
    public function test_daily_series_and_top_products_skip_cancelled_orders(): void
    {
        Mail::fake();
        $eggs = $this->product('Vajcia', '2.00', 100);
        $milk = $this->product('Mlieko', '1.00', 100);

        $this->post(route('cart.add'), ['product_id' => $eggs->id, 'quantity' => 10]);
        $this->post(route('checkout.store'), $this->checkoutData());
        $this->post(route('cart.add'), ['product_id' => $milk->id, 'quantity' => 3]);
        $this->post(route('checkout.store'), $this->checkoutData());
        Order::query()->latest('id')->first()->update(['status' => OrderStatus::Cancelled]);

        $report = app(SalesReport::class);
        $daily = $report->daily(7, 'EUR');

        $this->assertCount(7, $daily['dates']);
        $this->assertSame(1, end($daily['orders']));
        $this->assertSame(Order::query()->where('status', '!=', OrderStatus::Cancelled)->sum('total'), end($daily['revenue']));
        $this->assertSame([['name' => 'Vajcia', 'product_id' => $eggs->id, 'quantity' => 10]], $report->topProducts(30));

        $this->actingAs($this->superAdmin())->get('/admin')->assertOk();
        Livewire::test(TopProducts::class)->assertSee('Vajcia')->assertSee('10 ks');
        Livewire::test(UnpaidOrders::class)->assertSee(Order::query()->oldest('id')->value('number'));
        Livewire::test(SalesChart::class)->assertOk();
    }
}
