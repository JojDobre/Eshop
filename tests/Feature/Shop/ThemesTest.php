<?php

namespace Tests\Feature\Shop;

use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
use App\Filament\Resources\MenuItems\Pages\ManageMenuItems;
use App\Models\MenuItem;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;

class ThemesTest extends ShopTestCase
{
    private string $themesPath;

    protected function setUp(): void
    {
        parent::setUp();

        // Dočasný priečinok so šablónou Default a odvodenou šablónou „child“.
        $this->themesPath = sys_get_temp_dir().'/eshop-themes-'.uniqid();
        File::copyDirectory(base_path('themes/default'), $this->themesPath.'/default');
        File::ensureDirectoryExists($this->themesPath.'/child/partials');
        File::ensureDirectoryExists($this->themesPath.'/child/templates');
        File::ensureDirectoryExists($this->themesPath.'/child/lang/sk');
        File::put($this->themesPath.'/child/theme.json', json_encode([
            'name' => 'child',
            'version' => '1.0.0',
            'parent' => 'default',
            'title' => ['sk' => 'Odvodená'],
            'settings' => [
                'products_per_row' => ['type' => 'select', 'default' => '3', 'options' => ['3' => '3', '4' => '4']],
                'badge_text' => ['type' => 'text', 'default' => 'Novinka'],
            ],
        ]));
        File::put($this->themesPath.'/child/partials/cookie-bar.blade.php', '<div id="child-partial">{{ $themeOptions->get(\'badge_text\') }}</div>');
        File::put($this->themesPath.'/child/lang/sk/shop.php', "<?php return ['search' => 'Hľadať v odvodenej'];");
        File::put($this->themesPath.'/child/templates/category-kava.blade.php', "@extends('theme::layouts.app')\n@section('content')<p>Šablóna pre kávu</p>@endsection");
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themesPath);

        parent::tearDown();
    }

    private function useTheme(string $name): void
    {
        $settings = app(GeneralSettings::class);
        $settings->theme = $name;
        $settings->save();

        $this->app->singleton(ThemeManager::class, fn () => new ThemeManager($this->themesPath, 'default', fn () => $name));
        $this->app->forgetScopedInstances();
        View::replaceNamespace('theme', app(ThemeManager::class)->viewPaths());
        app('translator')->setLoaded([]);
    }

    public function test_child_theme_overrides_files_translations_and_inherits_the_rest(): void
    {
        $this->useTheme('child');
        $this->category('Káva');

        $this->get('/')
            ->assertOk()
            ->assertSee('id="child-partial"', false)
            ->assertSee('Novinka')
            ->assertSee('Hľadať v odvodenej')
            // Ostatné preklady prevezme od rodiča.
            ->assertSee('Košík je prázdny');

        $this->get('/kategoria/kava')->assertOk()->assertSee('Šablóna pre kávu');

        $definitions = app(ThemeManager::class)->settingDefinitions();
        $this->assertArrayHasKey('button_style', $definitions);
        $this->assertSame('3', app(ThemeOptions::class)->get('products_per_row'));
    }

    public function test_theme_settings_change_the_storefront(): void
    {
        $this->useTheme('default');
        $this->product('Káva');
        $options = app(ThemeOptions::class);
        $theme = app(ThemeManager::class)->active();

        $this->assertSame('rounded', $options->get('button_style'));

        $options->save($theme, ['button_style' => 'pill', 'announcement' => 'Doprava zadarmo nad 50 €', 'products_per_row' => '9', 'font' => 'serif']);

        $this->assertSame('4', app(ThemeOptions::class)->get('products_per_row'));
        $this->get('/')
            ->assertSee('--theme-button-radius: 9999px', false)
            ->assertSee('Georgia', false)
            ->assertSee('Doprava zadarmo nad 50 €');
    }

    public function test_menus_replace_default_navigation(): void
    {
        $this->useTheme('default');
        $coffee = $this->category('Káva');
        $this->category('Čaj');

        $this->get('/')->assertSee('Čaj');

        $parent = MenuItem::query()->create(['location' => 'header', 'type' => 'category', 'target' => (string) $coffee->id]);
        MenuItem::query()->create(['location' => 'header', 'parent_id' => $parent->id, 'type' => 'url', 'target' => '/akcia', 'label' => 'Akciové kávy']);
        MenuItem::query()->create(['location' => 'footer', 'type' => 'url', 'target' => 'https://example.com', 'label' => 'Blog', 'new_window' => true]);

        $this->get('/')
            ->assertSee($coffee->url())
            ->assertSee('Akciové kávy')
            ->assertSee('href="https://example.com"', false);
    }

    public function test_admin_manages_menu_items(): void
    {
        $this->useTheme('default');
        $coffee = $this->category('Káva');
        $this->actingAs($this->superAdmin());

        $this->get('/admin/menus')->assertOk();

        Livewire::test(ManageMenuItems::class)
            ->callAction('create', ['location' => 'header', 'type' => 'category', 'target' => (string) $coffee->id])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('menu_items', ['location' => 'header', 'type' => 'category', 'target' => (string) $coffee->id]);
    }
}
