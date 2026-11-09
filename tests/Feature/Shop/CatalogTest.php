<?php

namespace Tests\Feature\Shop;

use Modules\Catalog\Models\Category;

class CatalogTest extends ShopTestCase
{
    public function test_category_lists_products_including_subcategories(): void
    {
        $coffee = $this->category('Káva');
        $beans = $this->category('Zrnková káva', $coffee);
        $this->product('Etiópia', category: $beans);
        $this->product('Kolumbia', category: $coffee);
        $this->product('Skrytá káva', category: $coffee, attributes: ['is_visible' => false]);

        $this->get($coffee->url())
            ->assertOk()
            ->assertSee('Etiópia')
            ->assertSee('Kolumbia')
            ->assertDontSee('Skrytá káva')
            ->assertSee('Zrnková káva');
    }

    public function test_hidden_category_and_product_return_404(): void
    {
        $category = $this->category('Skrytá');
        $category->update(['is_visible' => false]);
        $product = $this->product('Skrytý produkt', attributes: ['is_visible' => false]);

        $this->get('/kategoria/'.$category->slug)->assertNotFound();
        $this->get('/produkt/'.$product->slug)->assertNotFound()->assertSee('Stránka sa nenašla');
    }

    public function test_slugs_are_generated_and_unique(): void
    {
        $first = $this->product('Káva Espresso');
        $second = $this->product('Káva Espresso');

        $this->assertSame('kava-espresso', $first->slug);
        $this->assertSame('kava-espresso-2', $second->slug);
    }

    public function test_products_can_be_sorted_by_price(): void
    {
        $category = $this->category('Čaj');
        $this->product('Drahý čaj', '20.00', category: $category);
        $this->product('Lacný čaj', '5.00', category: $category);

        $this->get($category->url().'?sort=price_asc')->assertSeeInOrder(['Lacný čaj', 'Drahý čaj']);
        $this->get($category->url().'?sort=price_desc')->assertSeeInOrder(['Drahý čaj', 'Lacný čaj']);
    }

    public function test_product_page_shows_price_stock_and_structured_data(): void
    {
        $product = $this->product('Ručný mlynček', '49.00', 3);

        $this->get($product->url())
            ->assertOk()
            ->assertSee("49,00\u{00A0}€")
            ->assertSee('Posledné 3 ks')
            ->assertSee('Do košíka')
            ->assertSee('"@type":"Product"', false);
    }

    public function test_sold_out_product_cannot_be_added_from_product_page(): void
    {
        $product = $this->product('Rooibos', stock: 0);

        $this->get($product->url())->assertSee('Vypredané')->assertDontSee('Do košíka');
    }

    public function test_search_ignores_case_and_diacritics(): void
    {
        $this->product('Čerstvá KÁVA z Etiópie');
        $this->product('Zelený čaj');

        $this->get('/hladat?q=kava etiopie')->assertOk()->assertSee('Čerstvá KÁVA z Etiópie')->assertDontSee('Zelený čaj');
        $this->get('/hladat?q=k')->assertSee('Zadajte aspoň 2 znaky.');
    }

    public function test_home_page_lists_categories_and_latest_products(): void
    {
        $this->category('Príslušenstvo');
        $this->product('French press');

        $this->get('/')->assertOk()->assertSee('Príslušenstvo')->assertSee('French press')->assertSee('Novinky');
    }

    public function test_sitemap_and_robots(): void
    {
        $product = $this->product('Mlynček');

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee($product->url(), false)
            ->assertSee('/stranka/obchodne-podmienky', false);

        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('Sitemap: '.route('storefront.sitemap'));
    }

    public function test_default_pages_are_shown_in_footer_and_render(): void
    {
        $this->get('/')->assertSee('Obchodné podmienky')->assertSee('Ochrana osobných údajov');
        $this->get('/stranka/obchodne-podmienky')->assertOk()->assertSee('Toto je iba vzorová kostra');
    }

    public function test_content_without_translation_in_shop_language_does_not_break_pages(): void
    {
        // Napr. obchod prepnutý zo slovenčiny na češtinu: kategória má iba slovenský preklad.
        Category::query()->create(['name' => ['cs' => 'Káva CZ']]);

        $this->get('/')->assertOk()->assertSee('Káva CZ');
    }
}
