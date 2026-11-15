<?php

namespace Tests\Feature\Shop;

use App\Core\Demo\DemoImporter;
use App\Core\Themes\ThemeChecker;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
use App\Models\BlockLayout;
use App\Settings\GeneralSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Modules\Catalog\Models\Product;
use Modules\Pages\Models\Page;
use Modules\Reservations\Models\Booking;
use Modules\Reservations\Models\BookingResource;
use Modules\Reservations\Models\BookingService;

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
        // Prepísané šablóny modulov (napr. rezervácie v šablóne Štúdio) podľa novej šablóny.
        View::replaceNamespace('reservations', [...$themes->moduleOverridePaths('reservations'), base_path('modules/Reservations/resources/views')]);
        View::getFinder()->flush();
        app('translator')->setLoaded([]);
        $this->app->forgetScopedInstances();
    }

    public function test_bundled_themes_pass_the_check(): void
    {
        foreach (['default', 'fresh', 'starter', 'farma', 'dielna', 'studio'] as $name) {
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

    public function test_studio_theme_renders_services_booking_panel_and_reservation_pages(): void
    {
        Mail::fake();
        $this->travelTo(Carbon::parse('2027-05-10 08:00'));
        $this->activate('studio');

        // Bez služieb a nastavení: predvolené texty šablóny, bez rezervačného panela.
        $this->get('/')->assertOk()
            ->assertSee(__('theme::studio.steps_title'))
            ->assertSee('--studio-bg: #f6f2ec', false)
            ->assertDontSee(__('theme::studio.panel_title'));

        $mira = BookingResource::query()->create(['name' => 'Mira', 'schedule' => ['2' => [['09:00', '12:00'], ['13:00', '19:00']]]]);
        $cut = BookingService::query()->create(['name' => 'Strih a fúkaná', 'category' => 'Vlasy', 'duration_minutes' => 60, 'price' => 3500, 'currency' => 'EUR']);
        $nails = BookingService::query()->create(['name' => 'Gélová manikúra', 'category' => 'Nechty', 'duration_minutes' => 60, 'price' => 3200, 'currency' => 'EUR', 'payment_mode' => 'deposit', 'deposit' => 1000]);
        $cut->resources()->attach($mira);
        $nails->resources()->attach($mira);

        app(ThemeOptions::class)->save(app(ThemeManager::class)->find('studio'), [
            'stats' => "12 rokov | skúseností\n4 odborníčky | s certifikátmi",
            'member_1_name' => 'Mira Kováčová', 'member_1_role' => 'kaderníčka',
            'opening_hours' => 'Pondelok – Piatok | 8:00 – 20:00', 'contact_address' => 'Hlavná 12, Bratislava',
        ]);

        $this->get('/')->assertOk()
            ->assertSee(__('theme::studio.panel_title'))
            ->assertSee(route('reservations.service', $cut), false)
            ->assertSee('Strih a fúkaná')
            ->assertSee('Nechty')
            ->assertSee('12 rokov')
            ->assertSee('Mira Kováčová')
            ->assertSee('Hlavná 12, Bratislava')
            ->assertSee('google.com/maps/search', false);

        $this->get(route('reservations.index'))->assertOk()->assertSee('Vlasy')->assertSee('Gélová manikúra');

        // Utorok: počty voľných termínov pri dňoch a časy podľa časti dňa.
        $this->get(route('reservations.service', ['service' => $cut, 'date' => '2027-05-11', 'time' => '14:00']))->assertOk()
            ->assertSee(trans_choice('theme::studio.free_slots', 9, ['count' => 9]))
            ->assertSee(__('theme::studio.part_morning'))
            ->assertSee(__('theme::studio.part_afternoon'))
            ->assertSee(__('theme::studio.part_evening'))
            ->assertSee('14:00 – 15:00');

        $this->post(route('reservations.book', $cut), ['date' => '2027-05-11', 'time' => '14:00', 'customer_name' => 'Jana', 'email' => 'jana@example.com', 'terms' => '1'])
            ->assertRedirect();
        $booking = Booking::query()->sole();
        $this->get($booking->url())->assertOk()
            ->assertSee(__('theme::studio.booked_title'))
            ->assertSee('jana@example.com')
            ->assertSee('Hlavná 12, Bratislava');
    }

    public function test_studio_bundled_demo_imports_with_reservations_and_translations(): void
    {
        config(['eshop.backup_path' => sys_get_temp_dir().'/studio-demo-'.uniqid()]);
        $theme = app(ThemeManager::class)->find('studio');
        $this->assertNotNull($theme->demoPath());

        $counts = app(DemoImporter::class)->import($theme->demoPath(), replace: true, backup: false);

        $this->assertSame(10, $counts['booking_services']);
        $this->assertSame(4, $counts['booking_resources']);
        $this->assertSame('studio', app(GeneralSettings::class)->theme);
        $this->assertSame(['cs', 'en'], array_keys(app(ThemeOptions::class)->translations($theme)));
        $this->activate('studio');
        $this->get('/')->assertOk()->assertSee('Mira Kováčová')->assertSee('Masáž lávovými kameňmi')->assertSee('Hlavná 12, Bratislava');
        $service = BookingService::query()->where('name->sk', 'Farbenie a melír')->sole();
        $this->assertSame('Hair', $service->getTranslation('category', 'en'));
        $this->assertCount(1, $service->resources);
        $this->get(route('reservations.service', $service))->assertOk()->assertSee('Farbenie a melír');

        Storage::disk('public')->deleteDirectory('demo/studio');
    }
}
