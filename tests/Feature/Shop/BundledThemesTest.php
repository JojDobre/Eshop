<?php

namespace Tests\Feature\Shop;

use App\Core\Demo\DemoImporter;
use App\Core\Themes\ThemeChecker;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
use App\Models\BlockLayout;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Modules\Catalog\Models\Product;
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
        foreach (['default', 'fresh', 'starter', 'farma', 'dielna'] as $name) {
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

    public function test_farma_home_sections_order_layouts_and_header_options(): void
    {
        $this->product('Japonská nosná', '0.40', category: $this->category('Vajcia'));
        $this->activate('farma');
        BlockLayout::query()->updateOrCreate(['key' => BlockLayout::HOME], ['blocks' => ['sk' => [['type' => 'text', 'data' => ['title' => 'Blok z obsahu', 'content' => '<p>Text bloku</p>']]]]]);

        // Bez nastaveného poradia a s vlastnými blokmi sa zobrazia iba bloky (ako doteraz).
        $this->get('/')->assertOk()->assertSee('Blok z obsahu')->assertDontSee('Japonská nosná');

        app(ThemeOptions::class)->save(app(ThemeManager::class)->find('farma'), [
            'home_sections' => [['section' => 'about', 'layout' => 'reverse'], ['section' => 'blocks'], ['section' => 'products'], ['section' => 'neznáma']],
            'about_title' => 'Rodinná farma', 'about_text' => '<p><strong>Od roku 2017</strong></p><script>alert(1)</script>',
            'benefit_1_title' => 'Vlastný chov', 'benefit_1_icon' => 'heart', 'benefit_2_title' => '-',
            'banner_image' => 'theme/banner.jpg',
            'header_layout' => 'center', 'logo_text' => 'Rodinná farma od roku 2017',
        ]);

        $html = $this->get('/')->assertOk()
            ->assertSee('<strong>Od roku 2017</strong>', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('Vlastný chov')
            ->assertDontSee(__('theme::farma.benefit_fresh'))
            ->assertSee(__('theme::farma.benefit_advice'))
            ->assertSee('Rodinná farma od roku 2017')
            ->assertSee('md:order-2 md:justify-center', false)
            ->assertDontSee('theme/banner.jpg', false)
            ->getContent();

        // Poradie: O nás → bloky → produkty.
        $this->assertTrue(strpos($html, 'Rodinná farma</h2>') < strpos($html, 'Blok z obsahu') && strpos($html, 'Blok z obsahu') < strpos($html, 'Japonská nosná'));

        // Baner iba s obrázkom (bez nadpisu a textu).
        app(ThemeOptions::class)->save(app(ThemeManager::class)->find('farma'), ['home_sections' => ['banner'], 'banner_image' => 'theme/banner.jpg', 'banner_link' => '/akcia']);
        $this->get('/')->assertOk()->assertSee('uploads/theme/banner.jpg', false)->assertSee('href="/akcia"', false);
    }

    public function test_starter_theme_loads_plain_stylesheet_and_page_template(): void
    {
        $this->activate('starter');
        $page = Page::query()->create(['name' => 'O nás', 'slug' => 'o-nas', 'content' => '<p>Sme malý obchod.</p>']);

        $this->get('/')->assertOk()->assertSee(theme_asset('custom.css', 'starter'), false);
        $this->get($page->url())->assertOk()->assertSee('Sme malý obchod.');
        $this->get(theme_asset('custom.css', 'starter'))->assertOk();
    }

    public function test_dielna_theme_renders_default_texts_settings_and_sections(): void
    {
        $ceramics = $this->category('Keramika');
        $this->product('Hrnček Piesok', '28.00', category: $ceramics);
        $this->activate('dielna');

        // Bez nastavení: predvolené texty šablóny v jazyku obchodu.
        $this->get('/')
            ->assertOk()
            ->assertSee(__('theme::dielna.categories_title'))
            ->assertSee('Hrnček Piesok')
            ->assertSee('--dielna-bg: #f7f1e8', false)
            ->assertSee('<em class="font-normal text-accent">pomaly</em>', false);

        app(ThemeOptions::class)->save(app(ThemeManager::class)->find('dielna'), [
            'home_sections' => [['section' => 'story', 'layout' => 'reverse'], ['section' => 'hero', 'layout' => 'default']],
            'hero_title' => 'Ručne *točené* hrnčeky', 'notice_text' => 'Doprava zdarma od 50 €', 'logo_text' => 'z mojej dielne',
            'story_signature' => 'Lucia', 'story_text' => '<p>Môj príbeh</p><script>alert(1)</script>', 'color_ink' => '#112233',
        ]);

        $html = $this->get('/')->assertOk()
            ->assertSee('Doprava zdarma od 50 €')
            ->assertSee('z mojej dielne')
            ->assertSee('<em class="font-normal text-accent">točené</em>', false)
            ->assertSee('Môj príbeh')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('--dielna-ink: #112233', false)
            ->assertDontSee(__('theme::dielna.categories_title'))
            ->getContent();
        // Poradie sekcií podľa nastavenia: príbeh pred úvodom.
        $this->assertTrue(strpos($html, 'Môj príbeh') < strpos($html, 'točené'));

        $this->get($ceramics->url())->assertOk()->assertSee('Hrnček Piesok');
        $this->get(Product::query()->first()->url())->assertOk();
        $this->get(route('cart.show'))->assertOk();
    }

    public function test_dielna_bundled_demo_imports(): void
    {
        config(['eshop.backup_path' => sys_get_temp_dir().'/dielna-demo-'.uniqid()]);
        $theme = app(ThemeManager::class)->find('dielna');
        $this->assertNotNull($theme->demoPath());

        $counts = app(DemoImporter::class)->import($theme->demoPath(), replace: true, backup: false);

        $this->assertSame(15, $counts['products']);
        $this->assertSame('dielna', app(GeneralSettings::class)->theme);
        $this->activate('dielna');
        $this->get('/')->assertOk()->assertSee('Lucia')->assertSee('Keramika')->assertSee('Drevené lyžičky, sada 3 ks');
        $this->get(Product::query()->where('name->sk', 'Hrnček Piesok')->sole()->url())->assertOk()->assertSee('Glazúra')->assertSee('Šalvia');
        $this->get(Page::query()->where('name->sk', 'Výroba na mieru')->sole()->url())->assertOk()->assertSee('Poslať požiadavku');

        Storage::disk('public')->deleteDirectory('demo/dielna');
    }
}
