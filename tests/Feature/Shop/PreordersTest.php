<?php

namespace Tests\Feature\Shop;

use App\Settings\CatalogSettings;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Modules\Cart\CartManager;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\StockLot;
use Modules\Catalog\Models\Warehouse;
use Modules\Catalog\StockLots;
use Modules\Orders\Actions\ChangeOrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;

class PreordersTest extends ShopTestCase
{
    private function enable(string $feature, bool $enabled = true): void
    {
        $settings = app(CatalogSettings::class);
        $settings->{'feature_'.$feature} = $enabled;
        $settings->save();
    }

    private function preorderProduct(int $stock = 0, ?int $limit = null): Product
    {
        return $this->product('Nová kniha', '20.00', stock: $stock, attributes: [
            'preorder_enabled' => true, 'preorder_available_at' => '2026-12-15', 'preorder_limit' => $limit,
        ]);
    }

    private function order(Product $product, int $quantity): Order
    {
        $this->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => $quantity]);
        $this->post(route('checkout.store'), $this->checkoutData());

        return Order::query()->latest('id')->firstOrFail();
    }

    public function test_out_of_stock_product_can_be_preordered_only_with_feature(): void
    {
        $book = $this->preorderProduct();
        $this->assertFalse($book->isAvailable());

        $this->enable('preorders');
        $book->refresh();
        $this->assertTrue($book->isAvailable());
        $this->assertTrue($book->isPreorder());

        $this->get($book->url())->assertSee('Na predobjednávku, očakávame 15. 12. 2026')->assertSee('Predobjednať');
        $this->get(route('cart.show'))->assertOk();

        $order = $this->order($book, 2);
        $item = $order->items->first();

        $this->assertTrue($item->is_preorder);
        $this->assertStringContainsString('predobjednávka', $item->fullName());
        $this->assertSame(-2, $book->fresh()->stock_quantity);
    }

    public function test_partial_preorder_and_limit(): void
    {
        $this->enable('preorders');
        $book = $this->preorderProduct(stock: 1, limit: 3);

        $this->assertSame(4, $book->maxPurchasableQuantity());
        $this->assertFalse($book->isPreorder(1));
        $this->assertTrue($book->isPreorder(2));

        $order = $this->order($book, 3);
        $this->assertSame([[1, false], [2, true]], array_map(fn ($allocation) => [$allocation['quantity'], ! empty($allocation['preorder'])], $order->items->first()->allocations));
        $this->assertSame(-2, $book->fresh()->stock_quantity);

        // Zostáva už iba 1 kus z limitu predobjednávok.
        $this->assertSame(1, $book->fresh()->maxPurchasableQuantity());
        $this->post(route('cart.add'), ['product_id' => $book->id, 'quantity' => 2])->assertSessionHas('error');
        $this->assertCount(0, app(CartManager::class)->summary()->lines);
    }

    public function test_cancelling_preorder_returns_stock(): void
    {
        $this->enable('preorders');
        $book = $this->preorderProduct(stock: 1);
        $order = $this->order($book, 3);

        app(ChangeOrderStatus::class)->handle($order, OrderStatus::Cancelled);
        $this->assertSame(1, $book->fresh()->stock_quantity);
    }

    public function test_receipt_covers_preorders_with_batches(): void
    {
        $this->enable('preorders');
        $this->enable('batches');
        $book = $this->preorderProduct();
        $this->order($book, 2);
        $this->assertSame(-2, $book->fresh()->stock_quantity);

        DB::transaction(fn () => app(StockLots::class)->receive($book->fresh(), null, Warehouse::default(), 5, 'B1'));
        $this->assertSame(3, $book->fresh()->stock_quantity);

        // Prijaté kusy najprv pokryjú predobjednávky, na predaj ostanú 3.
        app(StockLots::class)->reconcile($book->fresh());
        $this->assertSame(3, (int) StockLot::query()->sum('quantity'));
        $this->assertFalse($book->fresh()->isPreorder(3));
    }

    public function test_preorder_fields_in_product_form(): void
    {
        $book = $this->product('Kniha');
        $this->actingAs($this->superAdmin());

        Livewire::test(EditProduct::class, ['record' => $book->id])->assertFormFieldIsHidden('preorder_enabled');

        $this->enable('preorders');
        Livewire::test(EditProduct::class, ['record' => $book->id])
            ->assertFormFieldIsVisible('preorder_enabled')
            ->fillForm(['preorder_enabled' => true, 'preorder_limit' => 10])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($book->fresh()->preorder_enabled);
        $this->assertSame(10, $book->fresh()->preorder_limit);
    }
}
