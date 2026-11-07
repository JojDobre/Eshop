<?php

namespace Tests\Feature\Shop;

use Livewire\Livewire;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductVariant;
use Modules\Catalog\VariantGenerator;
use Modules\Orders\Actions\ChangeOrderStatus;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;

class VariantsTest extends ShopTestCase
{
    /**
     * Tričko s veľkosťami S, M a farbami červená, modrá → 4 varianty.
     */
    private function tshirt(): Product
    {
        $product = $this->product('Tričko', '15.00', stock: 0);

        $size = $product->options()->create(['name' => 'Veľkosť', 'position' => 1]);
        $size->values()->createMany([['value' => 'S', 'position' => 1], ['value' => 'M', 'position' => 2]]);
        $color = $product->options()->create(['name' => 'Farba', 'position' => 2]);
        $color->values()->createMany([['value' => 'červená', 'color' => '#dc2626', 'position' => 1], ['value' => 'modrá', 'color' => '#2563eb', 'position' => 2]]);

        app(VariantGenerator::class)->generate($product);

        foreach ($product->variants()->get() as $variant) {
            $variant->update(['stock_quantity' => 5]);
        }

        return $product->fresh();
    }

    private function variant(Product $product, string $name): ProductVariant
    {
        return $product->variants()->with('values')->get()->first(fn (ProductVariant $variant) => $variant->name() === $name);
    }

    public function test_generator_creates_all_combinations_once(): void
    {
        $product = $this->tshirt();

        $this->assertSame(4, $product->variants()->count());
        $this->assertSame(['S / červená', 'S / modrá', 'M / červená', 'M / modrá'], $product->variants()->with('values')->get()->map->name()->all());

        // Opakované spustenie nevytvorí duplicity.
        $this->assertSame(0, app(VariantGenerator::class)->generate($product));
    }

    public function test_variant_price_overrides_product_price(): void
    {
        $product = $this->tshirt();
        $this->variant($product, 'M / modrá')->setPrice('EUR', 1800);

        $product = Product::query()->with(['prices', 'variants.prices'])->find($product->id);

        $this->assertSame(1500, $product->priceFrom('EUR'));
        $this->assertTrue($product->hasVaryingPrices('EUR'));
        $this->get($product->category ?? '/')->assertSee("od 15,00\u{00A0}€");
    }

    public function test_product_page_shows_variant_picker(): void
    {
        $product = $this->tshirt();

        $this->get($product->url())->assertOk()
            ->assertSee('variantPicker', false)
            ->assertSee('Veľkosť')
            ->assertSee('#dc2626');
    }

    public function test_variant_must_be_chosen_and_stock_is_checked_per_variant(): void
    {
        $product = $this->tshirt();
        $variant = $this->variant($product, 'S / červená');

        $this->post(route('cart.add'), ['product_id' => $product->id])
            ->assertSessionHas('error', 'Vyberte si prosím variant produktu „Tričko".');

        $this->post(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 6])
            ->assertSessionHas('error', 'Produktu „Tričko (S / červená)" máme na sklade iba 5 ks.');

        $this->post(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2])
            ->assertSessionHas('cart_added', 'Tričko (S / červená)');

        $this->get(route('cart.show'))->assertSee('S / červená');
    }

    public function test_variant_of_another_product_is_rejected(): void
    {
        $product = $this->tshirt();
        $other = $this->product('Mikina');

        $this->post(route('cart.add'), ['product_id' => $other->id, 'variant_id' => $product->variants()->first()->id])
            ->assertSessionHas('error');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_order_with_variant_updates_variant_stock(): void
    {
        $product = $this->tshirt();
        $variant = $this->variant($product, 'M / modrá');
        $variant->setPrice('EUR', 1800);

        $this->post(route('cart.add'), ['product_id' => $product->id, 'variant_id' => $variant->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutData());

        $order = Order::query()->with('items')->firstOrFail();
        $item = $order->items->first();
        $this->assertSame('M / modrá', $item->variant_name);
        $this->assertSame('Tričko – M / modrá', $item->fullName());
        $this->assertSame(1800, $item->unit_price);
        $this->assertSame(3, $variant->fresh()->stock_quantity);
        $this->assertDatabaseHas('stock_movements', ['product_variant_id' => $variant->id, 'type' => 'order', 'quantity' => -2, 'stock_after' => 3]);

        app(ChangeOrderStatus::class)->handle($order, OrderStatus::Cancelled);
        $this->assertSame(5, $variant->fresh()->stock_quantity);
    }

    public function test_variants_can_be_generated_from_admin(): void
    {
        $product = $this->product('Mikina');
        $option = $product->options()->create(['name' => 'Veľkosť']);
        $option->values()->createMany([['value' => 'L'], ['value' => 'XL']]);
        $this->actingAs($this->superAdmin());

        Livewire::test(VariantsRelationManager::class, ['ownerRecord' => $product, 'pageClass' => EditProduct::class])
            ->callTableAction('generate')
            ->assertCanSeeTableRecords($product->variants()->get());

        $this->assertSame(2, $product->variants()->count());
        $this->get('/admin/products/'.$product->id.'/edit')->assertOk();
    }
}
