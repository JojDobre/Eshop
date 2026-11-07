<?php

namespace Tests\Feature\Installer;

use App\Core\Installer\Installation;
use App\Core\Installer\Installer;
use App\Settings\GeneralSettings;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalog\Models\Product;
use Modules\Pages\Models\Page;
use Modules\Payments\Models\PaymentMethod;
use Modules\Shipping\Models\ShippingMethod;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    // Inštalácia zapisuje do databázy, po každom teste sa preto databáza vráti do pôvodného stavu.
    use RefreshDatabase;

    public function test_everything_redirects_to_installer_before_installation(): void
    {
        $this->get('/')->assertRedirect(route('install.welcome'));
        $this->get('/admin')->assertRedirect(route('install.welcome'));
        $this->get('/admin/login')->assertRedirect(route('install.welcome'));
    }

    public function test_installer_pages_are_shown_in_the_chosen_language(): void
    {
        $this->get(route('install.welcome'), ['Accept-Language' => 'sk'])->assertOk()->assertSee('Vitajte');

        $this->post(route('install.locale'), ['locale' => 'cs'])->assertRedirect(route('install.requirements'));

        $this->get(route('install.requirements'))->assertOk()->assertSee('Kontrola webhostingu')->assertSee('pdo_mysql');
        $this->get(route('install.welcome'))->assertSee('Vítejte');
    }

    public function test_installer_language_follows_the_browser(): void
    {
        $this->get(route('install.welcome'), ['Accept-Language' => 'en-GB,en;q=0.9'])->assertSee('Welcome');
    }

    public function test_database_step_validates_input(): void
    {
        $this->post(route('install.database.save'), ['host' => '', 'port' => 'abc', 'prefix' => 'bad-prefix!'])
            ->assertSessionHasErrors(['host', 'port', 'database', 'username', 'prefix']);
    }

    public function test_shop_step_requires_a_verified_database(): void
    {
        $this->get(route('install.shop'))->assertRedirect(route('install.database'));
    }

    public function test_installer_creates_admin_settings_and_marks_installation(): void
    {
        $user = $this->app->make(Installer::class)->install(
            shop: [
                'shop_name' => 'Kaviareň u Jožka',
                'contact_email' => 'info@kaviaren.sk',
                'url' => 'https://kaviaren.sk/',
                'locale' => 'cs',
                'currency' => 'CZK',
                'country' => 'CZ',
            ],
            admin: ['name' => 'Jozef', 'email' => 'jozef@kaviaren.sk', 'password' => 'Heslo12345'],
        );

        $this->assertTrue($this->app->make(Installation::class)->isInstalled());
        $this->assertTrue($user->isSuperAdmin());
        $this->assertSame('cs', $user->locale);

        $settings = $this->app->make(GeneralSettings::class);
        $this->assertSame('Kaviareň u Jožka', $settings->shop_name);
        $this->assertSame('CZK', $settings->default_currency);

        $env = Dotenv::parse((string) file_get_contents($this->tempPath.'/.env'));
        $this->assertSame('Kaviareň u Jožka', $env['APP_NAME']);
        $this->assertSame('https://kaviaren.sk', $env['APP_URL']);
        $this->assertSame('false', $env['APP_DEBUG']);
    }

    public function test_installer_creates_default_shop_data_and_optional_demo_catalog(): void
    {
        $this->app->make(Installer::class)->install(
            shop: ['shop_name' => 'Obchod', 'contact_email' => 'info@obchod.sk', 'url' => 'https://obchod.sk', 'locale' => 'cs', 'currency' => 'CZK', 'country' => 'CZ'],
            admin: ['name' => 'Admin', 'email' => 'admin@obchod.sk', 'password' => 'Heslo12345'],
            withDemoData: true,
        );

        // Osobný odber, kuriér a „Bez dopravy“ (iba pre služby a digitálny tovar).
        $this->assertSame(3, ShippingMethod::count());
        $this->assertSame(4, PaymentMethod::count());
        // Platba kartou sa zapne až po nastavení platobnej brány.
        $this->assertFalse(PaymentMethod::query()->where('type', 'card')->firstOrFail()->is_active);
        $this->assertSame(4, Page::count());
        $this->assertSame(8, Product::count());

        // Ukážkové dáta sú v jazyku a mene obchodu.
        $product = Product::query()->with('prices')->firstOrFail();
        $this->assertArrayHasKey('cs', $product->getTranslations('name'));
        $this->assertNotNull($product->priceIn('CZK'));
        $this->assertSame(11900, ShippingMethod::query()->where('price', '>', 0)->value('price'));
    }

    public function test_full_web_installation_and_completion_page(): void
    {
        $this->withSession(['installer.database' => ['version' => '8.4.0', 'limited' => false]])
            ->get(route('install.shop'))
            ->assertOk()
            ->assertSee('8.4.0');

        $this->post(route('install.run'), [
            'shop_name' => 'Môj obchod',
            'contact_email' => 'info@obchod.sk',
            'url' => 'https://obchod.sk',
            'locale' => 'sk',
            'currency' => 'EUR',
            'country' => 'SK',
            'admin_name' => 'Admin',
            'admin_email' => 'admin@obchod.sk',
            'admin_password' => 'Heslo12345',
            'admin_password_confirmation' => 'Heslo12345',
        ])->assertRedirect(route('install.done'));

        $this->assertDatabaseHas('users', ['email' => 'admin@obchod.sk']);

        $this->get(route('install.done'))->assertOk()->assertSee('admin@obchod.sk')->assertSee('schedule:run');

        // Záverečná stránka sa zobrazí iba raz, zvyšok inštalátora je po inštalácii nedostupný.
        $this->get(route('install.done'))->assertNotFound();
        $this->get(route('install.welcome'))->assertNotFound();
    }

    public function test_weak_admin_password_is_rejected(): void
    {
        $this->withSession(['installer.database' => ['version' => '8.4.0', 'limited' => false]])
            ->post(route('install.run'), [
                'shop_name' => 'Obchod',
                'contact_email' => 'info@obchod.sk',
                'url' => 'https://obchod.sk',
                'locale' => 'sk',
                'currency' => 'EUR',
                'country' => 'SK',
                'admin_name' => 'Admin',
                'admin_email' => 'admin@obchod.sk',
                'admin_password' => 'short',
                'admin_password_confirmation' => 'short',
            ])->assertSessionHasErrors('admin_password');

        $this->assertFalse($this->app->make(Installation::class)->isInstalled());
    }
}
