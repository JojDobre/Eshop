<?php

namespace Tests\Feature\Shop;

use App\Settings\CatalogSettings;
use Modules\Catalog\Comparison\Comparison;
use Modules\Catalog\Comparison\ComparisonTable;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldType;
use Modules\Catalog\CustomFields\CustomFieldValues;
use Modules\Catalog\Models\Product;
use Modules\Catalog\Models\ProductType;

class ComparisonTest extends ShopTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(CatalogSettings::class)->fill(['feature_comparison' => true])->save();
    }

    private function field(string $key, string $name, CustomFieldType $type, array $attributes = []): CustomField
    {
        return CustomField::query()->create(['entity' => CustomField::PRODUCT, 'key' => $key, 'name' => $name, 'type' => $type, 'is_visible' => true, ...$attributes]);
    }

    private function values(Product $product, array $values): void
    {
        app(CustomFieldValues::class)->save(CustomField::PRODUCT, $product->id, $values);
    }

    public function test_compare_page_highlights_differences_and_best_values(): void
    {
        $power = $this->field('power', 'Výkon', CustomFieldType::Number, ['unit' => 'W', 'group' => 'Výkon', 'compare_best' => 'max']);
        $weight = $this->field('weight', 'Hmotnosť', CustomFieldType::Number, ['unit' => 'kg', 'group' => 'Rozmery', 'compare_best' => 'min']);
        $color = $this->field('color', 'Farba', CustomFieldType::Text);

        $a = $this->product('Vysávač A', '100.00');
        $b = $this->product('Vysávač B', '120.00');
        $this->values($a, ['power' => 800, 'weight' => 5, 'color' => 'čierna']);
        $this->values($b, ['power' => 1200, 'weight' => 6, 'color' => 'čierna']);

        $this->post(route('compare.add'), ['product_id' => $a->id])->assertSessionHas('success');
        $this->post(route('compare.add'), ['product_id' => $b->id]);
        $this->assertSame([$a->id, $b->id], app(Comparison::class)->ids());

        $groups = ComparisonTable::build(app(Comparison::class)->products());
        $rows = collect($groups[0]['rows'])->keyBy('key');
        $this->assertSame([$b->id], $rows['field_'.$power->id]['best']);
        $this->assertSame([$a->id], $rows['field_'.$weight->id]['best']);
        $this->assertSame([$a->id], $rows['price']['best']);
        $this->assertTrue($rows['field_'.$power->id]['differs']);
        $this->assertFalse($rows['field_'.$color->id]['differs']);
        $this->assertSame('Rozmery', $rows['field_'.$weight->id]['group']);

        $this->get(route('compare.show'))->assertOk()
            ->assertSee('Vysávač A')->assertSee('Vysávač B')->assertSee("1\u{00A0}200 W")
            ->assertSee(__('catalog::shop.compare_only_differences'))->assertSee('compare-differs', false)
            ->assertSee('porovnanie?p='.$a->id.'%2C'.$b->id, false);

        // Hlavička ukáže počet, detail produktu odkaz do porovnania.
        $this->get($a->url())->assertSee(__('catalog::shop.compare_link', ['count' => 2]))->assertSee(__('catalog::shop.compare_in_list'));
    }

    public function test_limits_types_shared_link_and_removal(): void
    {
        $phones = ProductType::query()->create(['name' => 'Telefóny']);
        $products = collect(range(1, 5))->map(fn ($i) => $this->product('Telefón '.$i, attributes: ['product_type_id' => $phones->id]));
        $other = $this->product('Puzdro');

        foreach ($products as $product) {
            $this->post(route('compare.add'), ['product_id' => $product->id]);
        }

        // Piaty produkt rovnakého typu sa nepridá, iný typ áno.
        $this->assertSame(4, app(Comparison::class)->count());
        $this->postJson(route('compare.add'), ['product_id' => $products[4]->id])->assertStatus(422);
        $this->post(route('compare.add'), ['product_id' => $other->id]);
        $groups = ComparisonTable::build(app(Comparison::class)->products());
        $this->assertCount(2, $groups);
        $this->assertSame('Telefóny', $groups[0]['type']);

        $this->delete(route('compare.remove', $products[0]->id));
        $this->assertFalse(app(Comparison::class)->has($products[0]->id));

        // Zdieľaný odkaz zobrazí produkty bez zmeny vlastného porovnania.
        $this->app['session']->flush();
        $this->get(route('compare.show', ['p' => $products[3]->id.','.$other->id]))->assertSee('Telefón 4')->assertSee('Puzdro')->assertDontSee(__('catalog::shop.compare_clear'));
        $this->assertSame(0, app(Comparison::class)->count());
    }

    public function test_feature_toggle(): void
    {
        $product = $this->product('Káva');
        app(CatalogSettings::class)->fill(['feature_comparison' => false])->save();

        $this->get(route('compare.show'))->assertNotFound();
        $this->post(route('compare.add'), ['product_id' => $product->id])->assertNotFound();
        $this->get($product->url())->assertDontSee(__('catalog::shop.compare_add'));
    }
}
