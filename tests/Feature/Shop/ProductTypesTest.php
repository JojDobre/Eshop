<?php

namespace Tests\Feature\Shop;

use App\Settings\CatalogSettings;
use Livewire\Livewire;
use Modules\Cart\CartManager;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Filament\Resources\ProductTypes\Pages\ManageProductTypes;
use Modules\Catalog\Models\ProductType;
use Modules\Orders\Models\Order;
use Modules\Payments\PaymentType;
use Modules\Shipping\Models\ShippingMethod;
use Modules\Shipping\ShippingType;

class ProductTypesTest extends ShopTestCase
{
    private function enableTypes(bool $enabled = true): void
    {
        $settings = app(CatalogSettings::class);
        $settings->feature_product_types = $enabled;
        $settings->save();
    }

    private function type(string $name): ProductType
    {
        return ProductType::query()->get()->first(fn (ProductType $type) => $type->getTranslation('name', 'sk') === $name);
    }

    public function test_default_types_exist_and_admin_page_follows_feature_switch(): void
    {
        $this->assertSame(8, ProductType::query()->count());
        $this->actingAs($this->superAdmin());

        $this->get('/admin/product-types')->assertForbidden();
        $this->enableTypes();
        $this->get('/admin/product-types')->assertOk()->assertSee('Potraviny');

        Livewire::test(ManageProductTypes::class)
            ->callAction('create', ['name' => 'Víno', 'unit' => 'l', 'requires_shipping' => true, 'is_unique' => false, 'position' => 9])
            ->assertHasNoActionErrors();
        $this->assertNotNull($this->type('Víno'));
    }

    public function test_unit_price_is_shown_for_food(): void
    {
        $this->enableTypes();
        $product = $this->product('Káva 250 g', '12.40', attributes: ['product_type_id' => $this->type('Potraviny')->id, 'unit_amount' => 0.25]);

        $this->assertSame(4960, $product->unitPriceIn());
        $this->get($product->url())->assertSee("49,60\u{00A0}€ / kg");

        // Vypnutím funkcie sa cena za jednotku nezobrazí (údaje ostanú).
        $this->enableTypes(false);
        $this->assertNull($product->fresh()->unitPriceIn());
    }

    public function test_unique_item_can_be_bought_only_once(): void
    {
        $this->enableTypes();
        $puppy = $this->product('Šteniatko Max', '300.00', stock: 1, attributes: ['product_type_id' => $this->type('Zvieratá')->id]);

        $this->post(route('cart.add'), ['product_id' => $puppy->id]);
        $this->post(route('cart.add'), ['product_id' => $puppy->id])->assertSessionHas('error');
        $this->assertSame(1, app(CartManager::class)->summary()->lines[0]->quantity);
        $this->assertSame(1, $puppy->fresh()->maxPurchasableQuantity());
    }

    public function test_service_only_cart_offers_no_shipping(): void
    {
        $this->enableTypes();
        $service = $this->product('Konzultácia', '50.00', attributes: ['product_type_id' => $this->type('Služby')->id]);
        $none = ShippingMethod::query()->where('type', ShippingType::None)->firstOrFail();

        $this->post(route('cart.add'), ['product_id' => $service->id]);
        $this->get(route('checkout.show'))->assertOk()->assertSee('Bez dopravy')->assertDontSee('Kuriér na adresu');

        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $this->courier()->id]))->assertSessionHasErrors('shipping_method_id');
        $this->post(route('checkout.store'), $this->checkoutData(['shipping_method_id' => $none->id, 'payment_method_id' => $this->payment(PaymentType::BankTransfer)->id]));

        $order = Order::query()->firstOrFail();
        $this->assertSame(0, $order->shipping_price);
        $this->assertNull($order->shipping_street);
    }

    public function test_regular_cart_does_not_offer_no_shipping(): void
    {
        $this->post(route('cart.add'), ['product_id' => $this->product('Mlynček')->id]);
        $this->get(route('checkout.show'))->assertSee('Kuriér na adresu')->assertDontSee('Bez dopravy');
    }

    public function test_product_form_shows_type_fields(): void
    {
        $this->enableTypes();
        $field = CustomField::query()->create(['entity' => 'product', 'key' => 'alergeny', 'name' => 'Alergény', 'type' => 'text']);
        $other = CustomField::query()->create(['entity' => 'product', 'key' => 'velkost', 'name' => 'Veľkosť', 'type' => 'text']);
        $food = $this->type('Potraviny');
        $food->customFields()->attach($field);
        $product = $this->product('Med', attributes: ['product_type_id' => $food->id]);
        $this->actingAs($this->superAdmin());

        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->assertFormFieldIsVisible('custom_fields.alergeny')
            ->assertFormFieldIsHidden('custom_fields.velkost')
            ->assertFormFieldIsVisible('unit_amount')
            ->fillForm(['unit_amount' => '0.5'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('0.5000', (string) $product->fresh()->unit_amount);
    }
}
