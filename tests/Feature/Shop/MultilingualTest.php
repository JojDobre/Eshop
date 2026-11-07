<?php

namespace Tests\Feature\Shop;

use App\Core\Content\ShopLocales;
use App\Filament\Pages\Settings\ManageGeneralSettings;
use App\Settings\GeneralSettings;
use Livewire\Livewire;
use Modules\Catalog\Filament\Resources\Products\Pages\EditProduct;
use Modules\Catalog\Models\Product;
use Modules\Orders\Mail\OrderConfirmation;
use Modules\Orders\Models\Order;

class MultilingualTest extends ShopTestCase
{
    private function enableLocales(string ...$locales): void
    {
        $settings = app(GeneralSettings::class);
        $settings->locales = $locales;
        $settings->save();
        app(ShopLocales::class)->refresh();
    }

    private function translatedProduct(): Product
    {
        $category = $this->category('Káva');
        $category->setTranslation('name', 'en', 'Coffee')->save();
        $product = $this->product('Etiópia zrnková', '12.90', category: $category);
        $product->setTranslation('name', 'en', 'Ethiopia beans')->save();

        return $product->fresh();
    }

    public function test_single_language_shop_has_no_prefixes_or_alternates(): void
    {
        $product = $this->translatedProduct();

        $this->get($product->url())->assertOk()->assertDontSee('hreflang', false);
        $this->get('/en/produkt/'.$product->getTranslation('slug', 'en'))->assertNotFound();
    }

    public function test_product_is_shown_in_language_by_prefix_with_translated_url(): void
    {
        $this->enableLocales('en', 'cs');
        $product = $this->translatedProduct();

        $this->assertSame('ethiopia-beans', $product->getTranslation('slug', 'en'));
        $this->assertSame('etiopia-zrnkova', $product->getTranslation('slug', 'sk'));

        $this->get('/produkt/etiopia-zrnkova')->assertOk()
            ->assertSee('Etiópia zrnková')->assertSee('Do košíka')
            ->assertSee('hreflang="en" href="'.url('/en/produkt/ethiopia-beans').'"', false)
            ->assertSee('hreflang="x-default"', false);

        $this->get('/en/produkt/ethiopia-beans')->assertOk()
            ->assertSee('<html lang="en">', false)
            ->assertSee('Ethiopia beans')->assertSee('Add to cart')
            ->assertSee(url('/en/kategoria/coffee'), false)
            ->assertSee(url('/en/kosik'), false);

        // Čeština bez prekladu: obsah v predvolenom jazyku, adresa s predponou /cz/.
        $this->get('/cz/produkt/etiopia-zrnkova')->assertOk()->assertSee('Etiópia zrnková')->assertSee('<html lang="cs">', false);

        // Predvolený jazyk s predponou presmeruje na adresu bez predpony.
        $this->get('/sk/produkt/etiopia-zrnkova')->assertRedirect(url('/produkt/etiopia-zrnkova'));
        $this->get('/de/produkt/etiopia-zrnkova')->assertNotFound();
    }

    public function test_sitemap_lists_all_languages(): void
    {
        $this->enableLocales('en');
        $this->translatedProduct();

        $this->get('/sitemap.xml')->assertOk()
            ->assertSee(url('/produkt/etiopia-zrnkova'), false)
            ->assertSee(url('/en/produkt/ethiopia-beans'), false)
            ->assertSee(url('/en'), false);
    }

    public function test_order_placed_in_english_keeps_language(): void
    {
        $this->enableLocales('en');
        $product = $this->translatedProduct();

        $this->post('/en/kosik', ['product_id' => $product->id]);
        $response = $this->post('/en/pokladna', $this->checkoutData());

        $order = Order::query()->firstOrFail();
        $this->assertSame('en', $order->locale);
        $this->assertStringContainsString('/en/objednavka/', $order->publicUrl());
        $response->assertRedirect($order->publicUrl());
        $this->get($order->publicUrl())->assertOk()->assertSee('Ethiopia beans');
        $this->assertSame('Ethiopia beans', $order->items->first()->name);

        // E-maily v jazyku objednávky, aj keď ich posiela administrácia v slovenčine.
        app()->setLocale('sk');
        $mail = new OrderConfirmation($order);
        $this->assertSame('Order confirmation no. '.$order->number, $mail->envelope()->subject);
        $this->assertStringContainsString('/en/objednavka/', $mail->render());
    }

    public function test_admin_edits_translations_with_language_switcher(): void
    {
        $this->enableLocales('en');
        $product = $this->product('Mlynček');
        $this->actingAs($this->superAdmin());

        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->assertActionVisible('editLocale_en')
            ->assertFormSet(['name' => 'Mlynček'])
            ->fillForm(['name' => 'Mlynček ručný'])
            ->callAction('editLocale_en')
            ->assertFormSet(['name' => null])
            ->fillForm(['name' => 'Hand grinder', 'description' => '<p>Great grinder</p>'])
            ->callAction('editLocale_sk')
            ->assertFormSet(['name' => 'Mlynček ručný'])
            ->assertHasNoFormErrors();

        $product->refresh();
        $this->assertSame('Mlynček ručný', $product->getTranslation('name', 'sk'));
        $this->assertSame('Hand grinder', $product->getTranslation('name', 'en'));
        $this->assertSame('hand-grinder', $product->getTranslation('slug', 'en'));
        $this->assertStringContainsString('Great grinder', (string) $product->getTranslation('description', 'en'));
    }

    public function test_admin_can_enable_languages(): void
    {
        $this->actingAs($this->superAdmin());

        Livewire::test(ManageGeneralSettings::class)
            ->fillForm(['locales' => ['en', 'de']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['sk', 'en', 'de'], app(ShopLocales::class)->enabled());
    }
}
