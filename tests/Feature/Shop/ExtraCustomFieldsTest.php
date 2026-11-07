<?php

namespace Tests\Feature\Shop;

use App\Core\Content\ShopLocales;
use App\Settings\CatalogSettings;
use App\Settings\GeneralSettings;
use Livewire\Livewire;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldValues;
use Modules\Catalog\Filament\Resources\Categories\Pages\EditCategory;
use Modules\Catalog\Filament\Resources\CustomFields\Pages\ManageCustomFields;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Customers\Filament\Resources\Customers\Pages\EditCustomer;
use Modules\Customers\Models\Customer;
use Modules\Orders\Models\Order;

class ExtraCustomFieldsTest extends ShopTestCase
{
    private function enable(): void
    {
        $settings = app(CatalogSettings::class);
        $settings->feature_extra_custom_fields = true;
        $settings->save();
    }

    public function test_translatable_product_field_value_per_language(): void
    {
        $general = app(GeneralSettings::class);
        $general->locales = ['en'];
        $general->save();
        app(ShopLocales::class)->refresh();

        CustomField::query()->create(['key' => 'material', 'name' => 'Materiál', 'type' => 'text', 'is_translatable' => true]);
        $product = $this->product('Tričko');
        $this->actingAs($this->superAdmin());

        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->fillForm(['custom_fields' => ['material' => 'bavlna']])
            ->call('save')
            ->callAction('editLocale_en')
            ->assertFormSet(['custom_fields.material' => null])
            ->fillForm(['name' => 'T-shirt', 'custom_fields' => ['material' => 'cotton']])
            ->call('save')
            ->assertHasNoFormErrors();

        $values = app(CustomFieldValues::class);
        $this->assertSame('bavlna', $values->get('product', $product->id, 'sk')['material']);
        $this->assertSame('cotton', $values->get('product', $product->id, 'en')['material']);

        $this->get($product->url())->assertSee('bavlna');
        $this->get('/en/produkt/'.$product->fresh()->getTranslation('slug', 'en'))->assertSee('cotton');
    }

    public function test_fields_for_other_objects_need_the_feature(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageCustomFields::class)
            ->callAction('create', ['entity' => 'category', 'name' => 'Ikona', 'type' => 'text'])
            ->assertHasNoActionErrors();
        $this->assertSame('product', CustomField::query()->firstOrFail()->entity);

        $this->enable();
        Livewire::test(ManageCustomFields::class)
            ->callAction('create', ['entity' => 'category', 'name' => 'Farba sekcie', 'type' => 'text'])
            ->assertHasNoActionErrors();
        $this->assertTrue(CustomField::query()->where('entity', 'category')->exists());
    }

    public function test_category_and_customer_fields_are_saved(): void
    {
        $this->enable();
        CustomField::query()->create(['entity' => 'category', 'key' => 'banner', 'name' => 'Text banneru', 'type' => 'text']);
        CustomField::query()->create(['entity' => 'customer', 'key' => 'zakaznicke_cislo', 'name' => 'Zákaznícke číslo', 'type' => 'text']);
        $category = $this->category('Káva');
        $customer = Customer::query()->create(['name' => 'Jana', 'email' => 'jana@example.com', 'password' => 'x']);
        $this->actingAs($this->superAdmin());

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->fillForm(['custom_fields' => ['banner' => 'Zľava na kávu']])->call('save')->assertHasNoFormErrors();
        Livewire::test(EditCustomer::class, ['record' => $customer->id])
            ->fillForm(['custom_fields' => ['zakaznicke_cislo' => 'Z-42']])->call('save')->assertHasNoFormErrors();

        $this->assertSame('Zľava na kávu', app(CustomFieldValues::class)->get('category', $category->id)['banner']);
        $this->assertSame('Z-42', app(CustomFieldValues::class)->get('customer', $customer->id)['zakaznicke_cislo']);
        $this->get('/admin/manufacturers')->assertOk();
    }

    public function test_customer_fills_order_fields_at_checkout(): void
    {
        $this->enable();
        CustomField::query()->create(['entity' => 'order', 'key' => 'text_na_tortu', 'name' => 'Text na tortu', 'type' => 'text', 'is_required' => true]);
        $product = $this->product('Torta');
        $this->post(route('cart.add'), ['product_id' => $product->id]);

        $this->get(route('checkout.show'))->assertSee('Text na tortu');
        $this->post(route('checkout.store'), $this->checkoutData())->assertSessionHasErrors('custom_fields.text_na_tortu');
        $this->post(route('checkout.store'), $this->checkoutData(['custom_fields' => ['text_na_tortu' => 'Všetko najlepšie, Jana!']]));

        $order = Order::query()->firstOrFail();
        $this->get($order->publicUrl())->assertSee('Všetko najlepšie, Jana!');
        $this->actingAs($this->superAdmin())->get('/admin/orders/'.$order->id)->assertSee('Všetko najlepšie, Jana!');
    }
}
