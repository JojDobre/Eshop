<?php

namespace Tests\Feature\Shop;

use App\Settings\CatalogSettings;
use Livewire\Livewire;
use Modules\Cart\CartManager;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Models\Addon;
use Modules\Catalog\Models\Product;
use Modules\Orders\Actions\ChangeOrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;

class AddonsAndBundlesTest extends ShopTestCase
{
    private function enable(string $feature, bool $enabled = true): void
    {
        $settings = app(CatalogSettings::class);
        $settings->{'feature_'.$feature} = $enabled;
        $settings->save();
    }

    private function engraving(Product $product): Addon
    {
        $addon = Addon::query()->create([
            'name' => 'Gravírovanie', 'price' => 500, 'currency' => 'EUR',
            'needs_text' => true, 'text_label' => 'Text gravírovania', 'text_max_length' => 30, 'is_active' => true,
        ]);
        $product->addons()->attach($addon);

        return $addon;
    }

    private function bundle(array $components, string $price = '25.00'): Product
    {
        $bundle = $this->product('Darčekový balíček', $price, attributes: ['is_bundle' => true, 'track_stock' => false]);

        foreach ($components as $position => [$product, $quantity]) {
            $bundle->bundleItems()->create(['product_id' => $product->id, 'quantity' => $quantity, 'position' => $position]);
        }

        return $bundle->fresh();
    }

    public function test_addon_raises_price_and_creates_separate_cart_line(): void
    {
        $this->enable('addons');
        $pen = $this->product('Pero', '20.00');
        $addon = $this->engraving($pen);

        $this->get($pen->url())->assertSee('Gravírovanie');

        $this->post(route('cart.add'), ['product_id' => $pen->id, 'addons' => [$addon->id => '1'], 'addon_texts' => [$addon->id => 'Pre Janu']]);
        $this->post(route('cart.add'), ['product_id' => $pen->id]);

        $lines = app(CartManager::class)->summary()->lines;
        $this->assertCount(2, $lines);
        $this->assertSame(2500, $lines[0]->unitPrice);
        $this->assertStringContainsString('Pre Janu', $lines[0]->addonsLabel());
        $this->assertSame(2000, $lines[1]->unitPrice);

        $this->post(route('checkout.store'), $this->checkoutData());
        $item = Order::query()->firstOrFail()->items()->orderBy('id')->first();
        $this->assertStringContainsString('Gravírovanie', $item->fullName());
        $this->assertStringContainsString('Pre Janu', $item->fullName());
    }

    public function test_addon_text_is_required_and_feature_switch_hides_addons(): void
    {
        $this->enable('addons');
        $pen = $this->product('Pero', '20.00');
        $addon = $this->engraving($pen);

        $this->post(route('cart.add'), ['product_id' => $pen->id, 'addons' => [$addon->id => '1']])->assertSessionHas('error');
        $this->assertCount(0, app(CartManager::class)->summary()->lines);

        $this->enable('addons', false);
        $this->get($pen->url())->assertDontSee('name="addons[', false);
        // Voľba, ktorá už nie je v ponuke, sa nepridá potichu bez príplatku – zákazník dostane chybu.
        $this->post(route('cart.add'), ['product_id' => $pen->id, 'addons' => [$addon->id => '1'], 'addon_texts' => [$addon->id => 'X']])->assertSessionHas('error');
        $this->post(route('cart.add'), ['product_id' => $pen->id]);
        $this->assertSame(2000, app(CartManager::class)->summary()->lines[0]->unitPrice);
    }

    public function test_bundle_availability_follows_components(): void
    {
        $this->enable('bundles');
        $mug = $this->product('Hrnček', '10.00', stock: 3);
        $coffee = $this->product('Káva', '9.00', stock: 10);
        $bundle = $this->bundle([[$mug, 1], [$coffee, 2]]);

        $this->assertSame(3, $bundle->maxPurchasableQuantity());
        $this->assertTrue($bundle->isAvailable(3));
        $this->assertFalse($bundle->isAvailable(4));
        $this->assertSame(2800, $bundle->bundleComponentsPrice());

        $this->get($bundle->url())->assertSee('Obsah balíčka')->assertSee('2 × Káva')->assertSee('Ušetríte');
    }

    public function test_bundle_order_deducts_components_and_cancel_returns_them(): void
    {
        $this->enable('bundles');
        $mug = $this->product('Hrnček', '10.00', stock: 3);
        $coffee = $this->product('Káva', '9.00', stock: 10);
        $bundle = $this->bundle([[$mug, 1], [$coffee, 2]]);

        $this->post(route('cart.add'), ['product_id' => $bundle->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData());
        $order = Order::query()->firstOrFail();

        $this->assertSame(1, $mug->fresh()->stock_quantity);
        $this->assertSame(6, $coffee->fresh()->stock_quantity);
        $this->assertCount(2, $order->items->first()->allocations);

        app(ChangeOrderStatus::class)->handle($order, OrderStatus::Cancelled);
        $this->assertSame(3, $mug->fresh()->stock_quantity);
        $this->assertSame(10, $coffee->fresh()->stock_quantity);

        app(ChangeOrderStatus::class)->handle($order->fresh(), OrderStatus::Processing);
        $this->assertSame(1, $mug->fresh()->stock_quantity);
    }

    public function test_bundle_cannot_be_ordered_over_component_stock(): void
    {
        $this->enable('bundles');
        $mug = $this->product('Hrnček', '10.00', stock: 1);
        $bundle = $this->bundle([[$mug, 2]]);

        $this->post(route('cart.add'), ['product_id' => $bundle->id])->assertSessionHas('error');
        $this->assertCount(0, app(CartManager::class)->summary()->lines);
    }

    public function test_bundle_items_are_edited_in_product_form(): void
    {
        $this->enable('bundles');
        $mug = $this->product('Hrnček', '10.00');
        $bundle = $this->product('Balíček');
        $this->actingAs($this->superAdmin());

        Livewire::test(EditProduct::class, ['record' => $bundle->id])
            ->fillForm(['is_bundle' => true])
            ->set('data.bundleItems', ['a' => ['product_id' => $mug->id, 'product_variant_id' => null, 'quantity' => 3]])
            ->call('save')
            ->assertHasNoFormErrors();

        $bundle->refresh();
        $this->assertTrue($bundle->is_bundle);
        $this->assertSame(3, $bundle->bundleItems->first()->quantity);
    }
}
