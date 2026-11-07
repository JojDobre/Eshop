<?php

namespace Tests\Feature\Shop;

use App\Settings\CatalogSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Catalog\Filament\Pages\ManageCatalogSettings;
use Modules\Catalog\Filament\Resources\StockLots\Pages\ListStockLots;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\StockLot;
use Modules\Catalog\Models\StockMovement;
use Modules\Catalog\Models\Warehouse;
use Modules\Catalog\StockLots;
use Modules\Orders\Actions\ChangeOrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;

class WarehousesAndBatchesTest extends ShopTestCase
{
    /**
     * @param  array<string, bool|int>  $values
     */
    private function settings(array $values): void
    {
        $settings = app(CatalogSettings::class);

        foreach ($values as $key => $value) {
            $settings->{$key} = $value;
        }

        $settings->save();
    }

    private function receive(Product $product, int $quantity, ?string $batch = null, ?string $expires = null, ?Warehouse $warehouse = null): StockLot
    {
        return DB::transaction(fn () => app(StockLots::class)->receive($product, null, $warehouse ?? Warehouse::default(), $quantity, $batch, null, $expires));
    }

    private function order(Product $product, int $quantity): Order
    {
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => $quantity]);
        $this->post(route('checkout.store'), $this->checkoutData());

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_orders_take_earliest_expiry_first_and_cancel_returns_to_same_batch(): void
    {
        $this->settings(['feature_batches' => true]);
        $milk = $this->product('Mlieko', '1.20', stock: 0);
        $later = $this->receive($milk, 2, 'L-B', today()->addDays(30)->toDateString());
        $sooner = $this->receive($milk, 3, 'L-A', today()->addDays(10)->toDateString());

        $this->assertSame(5, $milk->fresh()->stock_quantity);

        $order = $this->order($milk, 4);
        $item = $order->items->first();

        $this->assertSame(0, $sooner->fresh()->quantity);
        $this->assertSame(1, $later->fresh()->quantity);
        $this->assertSame(1, $milk->fresh()->stock_quantity);
        $this->assertSame('L-A (3), L-B (1)', $item->batchesLabel());
        $this->assertDatabaseHas('stock_movements', ['stock_lot_id' => $sooner->id, 'quantity' => -3, 'batch_number' => 'L-A', 'reference' => $order->number]);

        app(ChangeOrderStatus::class)->handle($order, OrderStatus::Cancelled);
        $this->assertSame(3, $sooner->fresh()->quantity);
        $this->assertSame(2, $later->fresh()->quantity);
        $this->assertSame(5, $milk->fresh()->stock_quantity);
    }

    public function test_existing_stock_moves_to_default_warehouse_and_manual_edits_stay_in_sync(): void
    {
        $product = $this->product('Hrnček', stock: 7);
        $this->settings(['feature_warehouses' => true]);

        app(StockLots::class)->reconcile($product);
        $lot = StockLot::query()->sole();
        $this->assertSame(7, $lot->quantity);
        $this->assertSame(Warehouse::default()->id, $lot->warehouse_id);

        $product->update(['stock_quantity' => 4]);
        $this->assertSame(4, $lot->fresh()->quantity);
    }

    public function test_inactive_warehouse_is_not_sold_and_warehouses_are_used_in_order(): void
    {
        $this->settings(['feature_warehouses' => true]);
        $main = Warehouse::default();
        $store = Warehouse::query()->create(['name' => 'Predajňa', 'position' => 5]);
        $product = $this->product('Kniha', stock: 0);
        $this->receive($product, 2, warehouse: $main);
        $this->receive($product, 3, warehouse: $store);

        $order = $this->order($product, 3);
        $this->assertSame([$main->id, $store->id], array_column($order->items->first()->allocations, 'warehouse_id'));

        $store->update(['is_active' => false]);
        $product->refresh();
        $this->assertSame(2, $product->stock_quantity);
        $this->assertSame(0, $product->sellableStock());
        $this->assertFalse($product->isAvailable());
    }

    public function test_expired_goods_are_not_sold_and_expiring_goods_get_discount(): void
    {
        $this->settings(['feature_batches' => true, 'feature_expiration' => true, 'expiration_warning_days' => 7, 'expiration_discount_percent' => 30, 'expiration_hide_days' => 2, 'expiration_show_date' => true]);
        $yogurt = $this->product('Jogurt', '1.00', stock: 0);
        $this->receive($yogurt, 5, 'OLD', today()->subDay()->toDateString());
        $this->receive($yogurt, 4, 'HIDE', today()->addDay()->toDateString());

        $yogurt->refresh();
        $this->assertSame(9, $yogurt->stock_quantity);
        $this->assertFalse($yogurt->isAvailable());

        $this->receive($yogurt, 3, 'SOON', today()->addDays(5)->toDateString());
        $yogurt->refresh();
        $this->assertSame(3, $yogurt->maxPurchasableQuantity());
        $this->assertSame(70, $yogurt->priceIn());
        $this->assertSame(100, $yogurt->compareAtPriceIn());

        $this->get($yogurt->url())->assertSee('zľava 30 %')->assertSee('Spotrebujte do '.today()->addDays(5)->format('j. n. Y'));

        $this->receive($yogurt, 10, 'FRESH', today()->addDays(60)->toDateString());
        $order = $this->order($yogurt, 4);
        $this->assertSame(70, $order->items->first()->unit_price);
        $this->assertSame('SOON (3), FRESH (1)', $order->items->first()->batchesLabel());

        // Vypnutím sledovania expirácie sa tovar predáva bez obmedzení a bez zľavy.
        $this->settings(['feature_expiration' => false]);
        $this->assertSame(100, $yogurt->fresh()->priceIn());
    }

    public function test_admin_can_receive_transfer_and_write_off(): void
    {
        $this->settings(['feature_warehouses' => true, 'feature_batches' => true, 'feature_expiration' => true]);
        $store = Warehouse::query()->create(['name' => 'Predajňa', 'position' => 5]);
        $product = $this->product('Syr', stock: 0);
        $this->actingAs($this->superAdmin());

        $this->get('/admin/warehouses')->assertOk()->assertSee('Predajňa');
        $this->get('/admin/stock')->assertOk();
        $this->get('/admin/products/'.$product->id.'/edit')->assertOk();

        Livewire::test(ListStockLots::class)
            ->callAction('receive', ['product_id' => $product->id, 'warehouse_id' => Warehouse::default()->id, 'batch_number' => 'S1', 'expires_at' => today()->subDay()->toDateString(), 'quantity' => 6])
            ->assertHasNoActionErrors();

        $lot = StockLot::query()->where('batch_number', 'S1')->sole();
        $this->assertSame(6, $product->fresh()->stock_quantity);

        Livewire::test(ListStockLots::class)
            ->callTableAction('transfer', $lot, ['warehouse_id' => $store->id, 'quantity' => 2])
            ->assertHasNoTableActionErrors();
        $this->assertSame(4, $lot->fresh()->quantity);
        $this->assertSame(2, StockLot::query()->where('warehouse_id', $store->id)->value('quantity'));
        $this->assertSame(6, $product->fresh()->stock_quantity);

        Livewire::test(ListStockLots::class)->callTableAction('writeOff', $lot, ['note' => 'Expirované']);
        $this->assertSame(0, $lot->fresh()->quantity);
        $this->assertSame(2, $product->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['type' => StockMovement::WRITE_OFF, 'quantity' => -4, 'note' => 'Expirované']);
    }

    public function test_pages_follow_feature_switches_and_settings_are_editable(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/warehouses')->assertForbidden();
        $this->get('/admin/stock')->assertForbidden();

        $this->settings(['feature_batches' => true, 'feature_expiration' => true]);
        $this->get('/admin/warehouses')->assertForbidden();
        $this->get('/admin/stock')->assertOk();

        Livewire::test(ManageCatalogSettings::class)
            ->fillForm(['expiration_warning_days' => 14, 'expiration_discount_percent' => 20, 'expiration_hide_days' => 1, 'expiration_show_date' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $settings = app(CatalogSettings::class)->refresh();
        $this->assertSame(14, $settings->expiration_warning_days);
        $this->assertSame(20, $settings->expiration_discount_percent);
    }
}
