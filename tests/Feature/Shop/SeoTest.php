<?php

namespace Tests\Feature\Shop;

use App\Filament\Resources\Redirects\Pages\ManageRedirects;
use App\Models\Redirect;
use App\Settings\SeoSettings;
use Livewire\Livewire;

class SeoTest extends ShopTestCase
{
    public function test_changed_product_slug_redirects_permanently(): void
    {
        $product = $this->product('Stará káva');
        $oldUrl = $product->url();

        $product->update(['slug' => 'nova-kava']);
        $this->assertNotSame($oldUrl, $product->url());

        $this->get($oldUrl)->assertStatus(301)->assertRedirect($product->url());
        $redirect = Redirect::query()->sole();
        $this->assertTrue($redirect->is_automatic);
        $this->assertSame(1, $redirect->hits);

        // Druhá zmena skráti reťaz: stará adresa vedie rovno na najnovšiu.
        $product->update(['slug' => 'najnovsia-kava']);
        $this->get($oldUrl)->assertRedirect($product->fresh()->url());
        $this->get('/produkt/nova-kava')->assertRedirect($product->fresh()->url());

        // Vypnuté automatické presmerovania.
        app(SeoSettings::class)->fill(['auto_redirects' => false])->save();
        $product->update(['slug' => 'bez-presmerovania']);
        $this->get('/produkt/najnovsia-kava')->assertNotFound();
    }

    public function test_manual_redirects_from_admin(): void
    {
        $product = $this->product('Káva');
        $this->actingAs($this->superAdmin());
        $this->get('/admin/settings/redirects')->assertOk();
        $this->get('/admin/settings/seo')->assertOk();

        Livewire::test(ManageRedirects::class)
            ->callAction('create', data: ['from_path' => 'https://stary-eshop.sk/shop/kava.html', 'to_url' => '/produkt/kava', 'status_code' => 302])
            ->assertHasNoActionErrors();

        $this->assertSame('/shop/kava.html', Redirect::query()->sole()->from_path);
        $this->get('/shop/kava.html')->assertStatus(302)->assertRedirect(url('/produkt/kava'));
        $this->get($product->url())->assertOk();
    }

    public function test_title_templates_structured_data_and_noindex_filters(): void
    {
        app(SeoSettings::class)->fill(['product_title' => '{name} | {category}', 'category_title' => 'Kúpiť {name} online', 'robots_extra' => 'Disallow: /test'])->save();
        $category = $this->category('Káva');
        $product = $this->product('Etiópia', category: $category);

        $this->get($product->url())->assertSee('<title>Etiópia | Káva', false)->assertSee('"BreadcrumbList"', false);
        // Prázdna premenná nenechá visieť oddeľovač.
        $this->get($this->product('Bez kategórie')->url())->assertSee('<title>Bez kategórie ·', false);

        $this->get($category->url())->assertSee('<title>Kúpiť Káva online', false)->assertDontSee('noindex');
        $this->get($category->url().'?sort=price_asc')->assertSee('noindex')->assertSee('<link rel="canonical" href="'.$category->url().'">', false);
        $this->get($category->url().'?page=2')->assertDontSee('noindex');

        $this->get('/')->assertSee('"SearchAction"', false)->assertSee('"Organization"', false);
        $this->get('/robots.txt')->assertSee('Disallow: /test');
    }
}
