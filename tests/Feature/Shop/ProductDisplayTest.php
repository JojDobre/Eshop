<?php

namespace Tests\Feature\Shop;

use App\Settings\CatalogSettings;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldType;
use Modules\Catalog\CustomFields\CustomFieldValues;
use Modules\Catalog\Models\AvailabilityStatus;
use Modules\Catalog\Models\ProductLabel;

class ProductDisplayTest extends ShopTestCase
{
    public function test_labels_custom_status_and_highlighted_field(): void
    {
        $label = ProductLabel::query()->create(['name' => 'Vlajková loď', 'color' => '#123456', 'text_color' => '#ffffff']);
        $status = AvailabilityStatus::query()->create(['name' => 'Na objednávku do 5 dní', 'color' => '#ff9900', 'is_purchasable' => true]);
        $product = $this->product('Vajcia', attributes: ['availability_status_id' => $status->id, 'description' => '<p>Popis</p><p>https://youtu.be/dQw4w9WgXcQ</p>']);
        $product->labels()->attach($label);
        $origin = CustomField::query()->create(['entity' => CustomField::PRODUCT, 'key' => 'origin', 'name' => 'Krajina pôvodu', 'type' => CustomFieldType::Text, 'is_visible' => true, 'display' => 'under_stock']);
        app(CustomFieldValues::class)->save(CustomField::PRODUCT, $product->id, ['origin' => 'Slovensko']);

        $this->get($product->url())
            ->assertOk()
            ->assertSee('Vlajková loď')
            ->assertSee('background-color: #123456', false)
            ->assertSee('Na objednávku do 5 dní')
            ->assertSee('Krajina pôvodu:')
            ->assertSee('Slovensko')
            ->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false)
            ->assertSee('product-description', false)
            ->assertSee('Do košíka');
        $this->get('/')->assertSee('Vlajková loď');

        // Stav, v ktorom sa nedá kúpiť.
        $status->update(['is_purchasable' => false]);
        $this->get($product->url())->assertDontSee('Do košíka');
        $this->post(route('cart.add'), ['product_id' => $product->id])->assertSessionHas('error');
    }

    public function test_display_settings_hide_vat_note_sku_and_stock(): void
    {
        $product = $this->product('Káva', stock: 3, attributes: ['sku' => 'KAVA-1']);
        $this->get($product->url())->assertSee('KAVA-1')->assertSee('Posledné 3 ks')->assertSee(__('theme::shop.price_includes_vat'));

        $settings = app(CatalogSettings::class);
        $settings->fill(['show_vat_note' => false, 'show_sku' => false, 'show_stock_quantity' => false, 'new_label_days' => 0])->save();

        $this->get($product->url())
            ->assertDontSee(__('theme::shop.sku').': KAVA-1')
            ->assertDontSee('Posledné 3 ks')
            ->assertSee('Skladom')
            ->assertDontSee(__('theme::shop.price_includes_vat'))
            ->assertDontSee(__('catalog::shop.new_label'));

        $settings->fill(['show_stock_status' => false])->save();
        $this->get($product->url())->assertDontSee('Skladom');
    }
}
