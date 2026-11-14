<?php

namespace Tests\Feature\Shop;

use App\Core\Content\ShopLocales;
use App\Core\Support\Currencies;
use App\Filament\Pages\Settings\ManageGeneralSettings;
use App\Settings\CurrencySettings;
use App\Settings\GeneralSettings;
use Livewire\Livewire;
use Modules\Pages\Models\Page;

class LocaleAndCurrencyFallbackTest extends ShopTestCase
{
    public function test_changing_default_language_keeps_products_and_pages(): void
    {
        $category = $this->category('Vajcia');
        $product = $this->product('Prepeličie vajcia', category: $category, attributes: ['description' => '<p>Čerstvé</p>']);
        $page = Page::query()->where('role', Page::ROLE_CONTACT)->sole();

        $this->actingAs($this->superAdmin());
        Livewire::test(ManageGeneralSettings::class)
            ->fillForm(['default_locale' => 'cs'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('cs', app(GeneralSettings::class)->default_locale);
        $product->refresh();
        $this->assertSame('Prepeličie vajcia', $product->getTranslation('name', 'cs', false));
        $this->assertSame('<p>Čerstvé</p>', $product->getTranslation('description', 'cs', false));
        $this->assertNotSame('', $page->fresh()->getTranslation('slug', 'cs', false));
    }

    public function test_untranslated_content_falls_back_to_default_language(): void
    {
        $product = $this->product('Prepeličie vajcia');
        $settings = app(GeneralSettings::class);
        $settings->locales_enabled = true;
        $settings->locales = ['cs'];
        $settings->save();
        app(ShopLocales::class)->refresh();

        $this->get('/cz/produkt/'.$product->getTranslation('slug', 'sk'))
            ->assertOk()
            ->assertSee('Prepeličie vajcia');
    }

    public function test_price_sorting_uses_converted_prices_in_other_currency(): void
    {
        $settings = app(CurrencySettings::class);
        $settings->multicurrency_enabled = true;
        $settings->enabled = ['CZK'];
        $settings->rates = ['CZK' => '25'];
        $settings->rounding = ['CZK' => Currencies::ROUND_MATH];
        $settings->save();
        app(Currencies::class)->refresh();

        $category = $this->category('Klietky');
        $cheap = $this->product('Lacná', '10.00', category: $category);          // 250 Kč prepočtom
        $this->product('Stredná', '20.00', category: $category)->setPrice('CZK', 40000); // 400 Kč zadaná
        $this->product('Drahá', '30.00', category: $category);                   // 750 Kč prepočtom
        $cheap->refresh();

        $this->post(route('storefront.currency'), ['currency' => 'CZK']);

        $this->get($category->url().'?sort=price_asc')->assertOk()->assertSeeInOrder(['Lacná', 'Stredná', 'Drahá']);
        $this->get($category->url().'?sort=price_desc')->assertOk()->assertSeeInOrder(['Drahá', 'Stredná', 'Lacná']);
    }
}
