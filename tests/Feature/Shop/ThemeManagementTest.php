<?php

namespace Tests\Feature\Shop;

use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemeOptions;
use App\Core\Themes\ThemePackages;
use App\Filament\Pages\CustomizeTheme;
use App\Filament\Pages\Themes;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use RuntimeException;
use ZipArchive;

class ThemeManagementTest extends ShopTestCase
{
    private string $themesPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->themesPath = sys_get_temp_dir().'/eshop-themes-'.uniqid();
        File::copyDirectory(base_path('themes/default'), $this->themesPath.'/default');
        config(['eshop.paths.themes' => $this->themesPath]);

        $this->app->singleton(ThemeManager::class, fn () => new ThemeManager($this->themesPath, 'default', fn () => app(GeneralSettings::class)->theme));
        $this->app->singleton(ThemePackages::class, fn ($app) => new ThemePackages($app->make(ThemeManager::class), $this->themesPath));
        $this->app->forgetScopedInstances();
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themesPath);

        parent::tearDown();
    }

    /**
     * @param  array<string, string>  $files
     */
    private function zip(array $files): string
    {
        $path = $this->themesPath.'/../upload-'.uniqid().'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE);

        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return $path;
    }

    private function childZip(): string
    {
        return $this->zip([
            'mobile/theme.json' => json_encode(['name' => 'mobile', 'version' => '1.0.0', 'parent' => 'default', 'title' => ['sk' => 'Mobilná'], 'requires' => ['eshop' => '^1.0']]),
            'mobile/partials/cookie-bar.blade.php' => '<div id="mobile-theme"></div>',
            'mobile/assets/style.css' => 'body { color: red; }',
        ]);
    }

    public function test_zip_upload_validates_package(): void
    {
        $packages = app(ThemePackages::class);
        $theme = $packages->install($this->childZip());

        $this->assertSame('mobile', $theme->name);
        $this->assertFileExists($this->themesPath.'/mobile/partials/cookie-bar.blade.php');
        $this->get(theme_asset('style.css', 'mobile'))->assertOk()->assertHeader('Content-Type', 'text/css; charset=utf-8');

        foreach ([
            ['../evil.php' => '<?php', 'theme.json' => '{"name":"evil"}'],
            ['x/theme.json' => '{"name":"x"}', 'x/run.exe' => 'MZ'],
            ['readme.txt' => 'no manifest'],
            ['y/theme.json' => '{"name":"y","requires":{"eshop":"^9.0"}}'],
        ] as $files) {
            try {
                $packages->install($this->zip($files));
                $this->fail('Balík mal byť odmietnutý.');
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }

        $this->expectException(RuntimeException::class);
        $packages->install($this->childZip());
    }

    public function test_admin_lists_activates_exports_and_deletes_themes(): void
    {
        app(ThemePackages::class)->install($this->childZip());
        $this->actingAs($this->superAdmin());

        $this->get('/admin/themes')->assertOk()->assertSee('Mobilná')->assertSee('Predvolená');

        Livewire::test(Themes::class)->callAction('activate', arguments: ['theme' => 'mobile'])->assertNotified();
        $this->assertSame('mobile', app(GeneralSettings::class)->refresh()->theme);
        $this->assertNotNull(app(ThemePackages::class)->cannotDelete(app(ThemeManager::class)->find('mobile')));

        $zip = app(ThemePackages::class)->export(app(ThemeManager::class)->find('mobile'));
        $this->assertFileExists($zip);

        Livewire::test(Themes::class)->callAction('activate', arguments: ['theme' => 'default']);
        Livewire::test(Themes::class)->callAction('delete', arguments: ['theme' => 'mobile']);
        $this->assertDirectoryDoesNotExist($this->themesPath.'/mobile');
    }

    public function test_preview_is_visible_only_to_admin(): void
    {
        app(ThemePackages::class)->install($this->childZip());

        $this->get('/?theme_preview=mobile')->assertOk()->assertDontSee('id="mobile-theme"', false);

        $this->actingAs($this->superAdmin());
        $this->get('/?theme_preview=mobile')->assertOk()->assertSee('id="mobile-theme"', false)->assertSee('Ukončiť náhľad');
        $this->get('/')->assertSee('id="mobile-theme"', false);
        $this->get('/?theme_preview=0')->assertDontSee('id="mobile-theme"', false);
    }

    public function test_customizer_previews_unsaved_values_and_saves(): void
    {
        $this->product('Káva');
        $this->actingAs($this->superAdmin());

        $this->get('/admin/themes/default/customize')->assertOk()->assertSee('Tvar tlačidiel');

        $page = Livewire::test(CustomizeTheme::class, ['theme' => 'default'])
            ->set('data.button_style', 'pill')
            ->assertDispatched('theme-preview-refresh');

        // Neuložené: zákazník vidí pôvodný vzhľad, náhľad nový.
        $this->get('/?theme_customize=1')->assertSee('--theme-button-radius: 9999px', false);
        auth()->logout();
        $this->get('/?theme_customize=1')->assertSee('--theme-button-radius: 0.5rem', false);

        $this->actingAs($this->superAdmin());
        $page->call('save');
        $this->assertSame('pill', app(ThemeOptions::class)->values(app(ThemeManager::class)->find('default'))['button_style']);
    }
}
