<?php

namespace Tests\Feature\Shop;

use Livewire\Livewire;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldType;
use Modules\Catalog\CustomFields\CustomFieldValues;
use Modules\Catalog\Filament\Resources\CustomFields\Pages\ManageCustomFields;
use Modules\Catalog\Filament\Resources\Products\Pages\CreateProduct;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Models\Product;

class CustomFieldsTest extends ShopTestCase
{
    private function fields(): array
    {
        return [
            'material' => CustomField::query()->create(['name' => 'Materiál', 'type' => CustomFieldType::Select, 'is_filterable' => true,
                'options' => [['label' => 'Bavlna'], ['label' => 'Ľan']]]),
            'power' => CustomField::query()->create(['name' => 'Výkon', 'type' => CustomFieldType::Number, 'unit' => 'W', 'is_filterable' => true]),
            'waterproof' => CustomField::query()->create(['name' => 'Vodeodolné', 'type' => CustomFieldType::Boolean, 'is_filterable' => true]),
            'origin' => CustomField::query()->create(['name' => 'Krajina pôvodu', 'type' => CustomFieldType::Text]),
            'internal' => CustomField::query()->create(['name' => 'Interná poznámka', 'type' => CustomFieldType::Text, 'is_visible' => false]),
        ];
    }

    public function test_keys_and_option_keys_are_generated(): void
    {
        $fields = $this->fields();

        $this->assertSame('material', $fields['material']->key);
        $this->assertSame('krajina_povodu', $fields['origin']->key);
        $this->assertSame(['bavlna' => 'Bavlna', 'lan' => 'Ľan'], $fields['material']->optionLabels());
        $this->assertSame(['sk' => 'Bavlna'], $fields['material']->options[0]['label']);
    }

    public function test_values_are_normalized_and_shown_on_product_page(): void
    {
        $this->fields();
        $product = $this->product('Ventilátor');

        app(CustomFieldValues::class)->save(CustomField::PRODUCT, $product->id, [
            'material' => 'lan', 'vykon' => '1500,5', 'vodeodolne' => true, 'krajina_povodu' => 'Slovensko',
            'interna_poznamka' => 'tajné',
        ]);

        $this->assertSame(1500.5, app(CustomFieldValues::class)->get(CustomField::PRODUCT, $product->id)['vykon']);

        $this->get($product->url())
            ->assertSee('Parametre')
            ->assertSee('Ľan')
            ->assertSee("1\u{00A0}500,5 W")
            ->assertSee('Slovensko')
            ->assertDontSee('tajné');
    }

    public function test_invalid_select_value_is_ignored(): void
    {
        $this->fields();
        $product = $this->product('Ventilátor');

        app(CustomFieldValues::class)->save(CustomField::PRODUCT, $product->id, ['material' => 'neexistuje']);

        $this->assertArrayNotHasKey('material', app(CustomFieldValues::class)->get(CustomField::PRODUCT, $product->id));
    }

    public function test_category_can_be_filtered_by_parameters(): void
    {
        $this->fields();
        $category = $this->category('Ventilátory');
        $values = app(CustomFieldValues::class);

        $small = $this->product('Malý ventilátor', category: $category);
        $big = $this->product('Veľký ventilátor', category: $category);
        $outdoor = $this->product('Vonkajší ventilátor', category: $category);
        $values->save(CustomField::PRODUCT, $small->id, ['material' => 'bavlna', 'vykon' => 30]);
        $values->save(CustomField::PRODUCT, $big->id, ['material' => 'lan', 'vykon' => 120]);
        $values->save(CustomField::PRODUCT, $outdoor->id, ['material' => 'lan', 'vykon' => 80, 'vodeodolne' => true]);

        $this->get($category->url())->assertSee('Filtre')->assertSee('Bavlna')->assertSee('Vodeodolné');

        $this->get($category->url().'?f[material][]=lan')
            ->assertSee('Veľký ventilátor')->assertSee('Vonkajší ventilátor')->assertDontSee('Malý ventilátor');

        $this->get($category->url().'?f[vykon][min]=50&f[vykon][max]=100')
            ->assertSee('Vonkajší ventilátor')->assertDontSee('Veľký ventilátor')->assertDontSee('Malý ventilátor');

        $this->get($category->url().'?f[vodeodolne]=1&f[material][]=lan')
            ->assertSee('Vonkajší ventilátor')->assertDontSee('Veľký ventilátor');
    }

    public function test_multiselect_filter_matches_any_selected_option(): void
    {
        CustomField::query()->create(['name' => 'Vhodné pre', 'type' => CustomFieldType::MultiSelect, 'is_filterable' => true,
            'options' => [['label' => 'Espresso'], ['label' => 'Filter'], ['label' => 'Moka']]]);
        $category = $this->category('Káva');
        $a = $this->product('Káva A', category: $category);
        $b = $this->product('Káva B', category: $category);
        app(CustomFieldValues::class)->save(CustomField::PRODUCT, $a->id, ['vhodne_pre' => ['espresso', 'moka']]);
        app(CustomFieldValues::class)->save(CustomField::PRODUCT, $b->id, ['vhodne_pre' => ['filter']]);

        $this->get($category->url().'?f[vhodne_pre][]=moka')->assertSee('Káva A')->assertDontSee('Káva B');
    }

    public function test_parameters_are_edited_in_product_form(): void
    {
        $this->fields();
        $this->actingAs($this->superAdmin());

        Livewire::test(CreateProduct::class)
            ->fillForm(['name' => 'Obrus', 'price' => '20', 'custom_fields' => ['material' => 'lan', 'vykon' => '5']])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::query()->firstOrFail();
        $this->assertSame('lan', app(CustomFieldValues::class)->get(CustomField::PRODUCT, $product->id)['material']);

        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->assertSchemaStateSet(['custom_fields.material' => 'lan'])
            ->fillForm(['custom_fields' => ['material' => 'bavlna', 'vykon' => '7', 'vodeodolne' => false, 'krajina_povodu' => '', 'interna_poznamka' => '']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('bavlna', app(CustomFieldValues::class)->get(CustomField::PRODUCT, $product->id)['material']);
    }

    public function test_custom_fields_can_be_managed_in_admin(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageCustomFields::class)
            ->callAction('create', ['name' => 'Rozmery', 'type' => 'text', 'unit' => 'cm', 'is_visible' => true])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('custom_fields', ['key' => 'rozmery', 'unit' => 'cm', 'entity' => 'product']);
        $this->get('/admin/custom-fields')->assertOk()->assertSee('Rozmery');
    }
}
