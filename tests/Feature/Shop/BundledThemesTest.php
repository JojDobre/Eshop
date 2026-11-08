<?php

namespace Tests\Feature\Shop;

use App\Core\Themes\ThemeChecker;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\View;
use Modules\Pages\Models\Page;

class BundledThemesTest extends ShopTestCase
{
    private function activate(string $name): void
    {
        $settings = app(GeneralSettings::class);
        $settings->theme = $name;
        $settings->save();

        $themes = app(ThemeManager::class);
        $themes->flush();
        View::replaceNamespace('theme', $themes->viewPaths());
        View::getFinder()->flush();
        app('translator')->setLoaded([]);
        $this->app->forgetScopedInstances();
    }

    public function test_bundled_themes_pass_the_check(): void
    {
        foreach (['default', 'fresh', 'starter', 'farma'] as $name) {
            $theme = app(ThemeManager::class)->find($name);
            $this->assertNotNull($theme, $name);
            $errors = collect(app(ThemeChecker::class)->check($theme))->where('level', 'error');
            $this->assertSame([], $errors->values()->all(), $name);
        }
    }

    public function test_fresh_theme_renders_its_own_layout(): void
    {
        $coffee = $this->category('Káva');
        $this->product('Etiópia', category: $coffee);
        $this->activate('fresh');

        $this->get('/')
            ->assertOk()
            ->assertSee('--fresh-accent: #166534', false)
            ->assertSee('Rýchle doručenie')
            ->assertSee('Etiópia');
        $this->get($coffee->url())->assertOk()->assertSee('Etiópia');
        $this->get(route('cart.show'))->assertOk();
    }

    public function test_farma_theme_renders_notice_sections_and_quick_add(): void
    {
        $eggs = $this->category('Násadové vajcia');
        $product = $this->product('Japonská nosná', '0.40', category: $eggs);
        $this->product('Anglická čierna', '0.65', 0, $eggs);
        $this->activate('farma');
        app(ThemeOptions::class)->save(app(ThemeManager::class)->find('farma'), [
            'notice_text' => 'Jarné liahnutie: −10 %', 'notice_code' => 'JAR10', 'notice_until' => now()->addDay()->toDateString(),
            'header_note' => 'Doprava zdarma od 60 €', 'banner_title' => 'Živé prepelice', 'banner_link' => '/', 'about_title' => 'Rodinná farma',
            'faq_1_q' => 'Ako balíte vajcia?', 'faq_1_a' => 'Každé zvlášť.', 'footer_color' => '#334455',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Jarné liahnutie: −10 %')
            ->assertSee('JAR10')
            ->assertSee('Doprava zdarma od 60 €')
            ->assertSee('Živé prepelice')
            ->assertSee('Rodinná farma')
            ->assertSee('"FAQPage"', false)
            ->assertSee('--farma-footer: #334455', false)
            ->assertSee('Japonská nosná')
            ->assertSee('Vypredané');

        // Tlačidlo „+“ pridá produkt rovno do košíka.
        $this->post(route('cart.add'), ['product_id' => $product->id])->assertRedirect();
        $this->get(route('cart.show'))->assertOk()->assertSee('Japonská nosná');
        $this->get($eggs->url())->assertOk()->assertSee('Anglická čierna');
        $this->get($product->url())->assertOk();
        $this->get(route('checkout.show'))->assertOk();

        // Po dátume platnosti sa oznam skryje.
        $this->travel(3)->days();
        $this->get('/')->assertDontSee('JAR10');
    }

    public function test_starter_theme_loads_plain_stylesheet_and_page_template(): void
    {
        $this->activate('starter');
        $page = Page::query()->create(['name' => 'O nás', 'slug' => 'o-nas', 'content' => '<p>Sme malý obchod.</p>']);

        $this->get('/')->assertOk()->assertSee(theme_asset('custom.css', 'starter'), false);
        $this->get($page->url())->assertOk()->assertSee('Sme malý obchod.');
        $this->get(theme_asset('custom.css', 'starter'))->assertOk();
    }
}
