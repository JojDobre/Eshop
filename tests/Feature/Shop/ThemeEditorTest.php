<?php

namespace Tests\Feature\Shop;

use App\Core\Themes\ThemeChecker;
use App\Core\Themes\ThemeFiles;
use App\Core\Themes\ThemeManager;
use App\Core\Themes\ThemePackages;
use App\Filament\Pages\ThemeEditor;
use App\Filament\Pages\Themes;
use App\Models\User;
use App\Settings\GeneralSettings;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use RuntimeException;
use Spatie\Permission\Models\Permission;

class ThemeEditorTest extends ShopTestCase
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
        $this->app->singleton(ThemeFiles::class, fn ($app) => new ThemeFiles($app->make(ThemeManager::class), $this->themesPath.'/../backups-'.basename($this->themesPath)));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->themesPath);
        File::deleteDirectory($this->themesPath.'/../backups-'.basename($this->themesPath));

        parent::tearDown();
    }

    private function enableEditor(): void
    {
        $settings = app(GeneralSettings::class);
        $settings->theme_editor_enabled = true;
        $settings->save();
    }

    public function test_child_theme_files_are_edited_with_backups_and_validation(): void
    {
        $files = app(ThemeFiles::class);
        $manager = app(ThemeManager::class);
        $child = $files->createChild($manager->find('default'), 'moja', 'Moja šablóna');

        $this->assertSame('default', $child->parent);
        $this->assertFalse($files->canEdit($manager->find('default')));

        $files->copyFromParent($child, 'partials/footer.blade.php');
        $this->assertContains('partials/footer.blade.php', $files->files($child));

        $files->write($child, 'partials/footer.blade.php', '<footer>Nová päta</footer>');
        $this->assertCount(1, $files->backups($child, 'partials/footer.blade.php'));

        // Syntaktická chyba sa neuloží.
        try {
            $files->write($child, 'partials/footer.blade.php', '@if($x) <?php echo ( ?>');
            $this->fail('Chybná šablóna sa nemala uložiť.');
        } catch (RuntimeException) {
            $this->assertSame('<footer>Nová päta</footer>', $files->read($child, 'partials/footer.blade.php'));
        }

        foreach (['../../.env', 'partials/x.exe', '/etc/passwd.php'] as $path) {
            try {
                $files->write($child, $path, 'x');
                $this->fail('Cesta '.$path.' mala byť odmietnutá.');
            } catch (RuntimeException) {
                $this->assertTrue(true);
            }
        }

        $stamp = array_key_first($files->backups($child, 'partials/footer.blade.php'));
        $files->restore($child, 'partials/footer.blade.php', $stamp);
        $this->assertStringContainsString('<footer', $files->read($child, 'partials/footer.blade.php'));
        $this->assertStringNotContainsString('Nová päta', $files->read($child, 'partials/footer.blade.php'));
    }

    public function test_editor_is_only_for_main_admin_and_can_be_disabled(): void
    {
        app(ThemeFiles::class)->createChild(app(ThemeManager::class)->find('default'), 'moja', 'Moja');
        $this->actingAs($admin = $this->superAdmin());

        $this->get('/admin/themes/moja/editor')->assertForbidden();

        $this->enableEditor();
        $this->get('/admin/themes/moja/editor')->assertOk();
        $this->get('/admin/themes/default/editor')->assertNotFound();

        Livewire::test(ThemeEditor::class, ['theme' => 'moja'])
            ->callAction('newFile', ['path' => 'partials/banner.blade.php'])
            ->set('content', '<div>Banner</div>')
            ->call('save')
            ->assertNotified();
        $this->assertFileExists($this->themesPath.'/moja/partials/banner.blade.php');

        // Bežný administrátor s oprávnením na nastavenia editor nevidí.
        $manager = User::factory()->create();
        $manager->givePermissionTo(Permission::findOrCreate('settings.manage', 'web'));
        $this->actingAs($manager)->get('/admin/themes/moja/editor')->assertForbidden();

        // Konfigurácia servera editor vypne úplne.
        config(['eshop.theme_editor' => false]);
        $this->actingAs($admin)->get('/admin/themes/moja/editor')->assertForbidden();
    }

    public function test_theme_check_reports_missing_parts(): void
    {
        $checker = app(ThemeChecker::class);
        $results = collect($checker->check(app(ThemeManager::class)->find('default')));
        $this->assertSame([], $results->where('level', 'error')->all());

        File::ensureDirectoryExists($this->themesPath.'/bad/layouts');
        File::put($this->themesPath.'/bad/theme.json', json_encode(['name' => 'bad', 'version' => '1.0.0']));
        File::put($this->themesPath.'/bad/layouts/app.blade.php', '<html><body><img src="x.png">@yield("content")</body></html>');
        app(ThemeManager::class)->flush();

        $results = collect($checker->check(app(ThemeManager::class)->find('bad')));
        $this->assertNotEmpty($results->where('level', 'error'));
        $this->assertTrue($results->contains(fn ($result) => str_contains($result['message'], 'cookies')));
        $this->assertTrue($results->contains(fn ($result) => str_contains($result['message'], 'alternatívneho')));

        $this->actingAs($this->superAdmin());
        Livewire::test(Themes::class)->mountAction('check', arguments: ['theme' => 'bad'])->assertActionMounted('check');
        Livewire::test(Themes::class)->callAction('createChild', ['parent' => 'default', 'title' => 'Moja', 'name' => 'moja-dva'])->assertNotified();
        $this->assertFileExists($this->themesPath.'/moja-dva/theme.json');
    }
}
