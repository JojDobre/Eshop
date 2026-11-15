<?php

namespace Tests\Feature;

use App\Core\Eshop;
use App\Core\Modules\ModuleManager;
use App\Core\Updates\UpdateChecker;
use App\Core\Updates\UpdateException;
use App\Core\Updates\UpdatePackage;
use App\Core\Updates\Updater;
use App\Models\User;
use App\Providers\AppServiceProvider;
use App\Settings\SystemSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Livewire\Features\SupportFileUploads\FileUploadConfiguration;
use Modules\Forms\Models\Form;
use ZipArchive;

class UpdaterTest extends InstalledTestCase
{
    private string $root;

    private string $secretKey;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/eshop-update-'.uniqid();
        config(['eshop.backup_path' => $this->root.'/storage/app/backups']);

        // Súčasná „inštalácia“.
        File::ensureDirectoryExists($this->root.'/app');
        File::put($this->root.'/app/Old.php', 'old');
        File::put($this->root.'/.env', 'APP_KEY=secret');
        File::ensureDirectoryExists($this->root.'/storage/app');

        $pair = sodium_crypto_sign_keypair();
        $this->secretKey = sodium_crypto_sign_secretkey($pair);
        config(['eshop.updates.public_key' => base64_encode(sodium_crypto_sign_publickey($pair))]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    private function package(string $version = '99.0.0', bool $sign = true, ?string $tamper = null): string
    {
        $inner = $this->root.'/inner.zip';
        $zip = new ZipArchive;
        $zip->open($inner, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('eshop/app/Old.php', 'new');
        $zip->addFromString('eshop/app/Added.php', 'added');
        $zip->addFromString('eshop/modules/Novy/module.json', '{}');
        $zip->addFromString('eshop/.env', 'APP_KEY=attacker');
        $zip->addFromString('eshop/storage/app/x.txt', 'x');
        $zip->close();

        $sha = hash_file('sha256', $inner);
        $manifest = ['version' => $version, 'sha256' => $sha, 'requires' => ['php' => '8.0.0'],
            'signature' => $sign ? base64_encode(sodium_crypto_sign_detached(UpdatePackage::message($version, $sha), $this->secretKey)) : null];

        $outer = $this->root.'/eshop-update.zip';
        $zip->open($outer, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($inner, 'package.zip');
        $zip->addFromString('manifest.json', json_encode($tamper ? [...$manifest, 'version' => $tamper] : $manifest));
        $zip->close();

        return $outer;
    }

    private function updater(?\Closure $migrate = null): Updater
    {
        return app(Updater::class)->using($this->root, $migrate ?? fn () => null, fn () => null);
    }

    public function test_signed_update_replaces_files_and_can_be_rolled_back(): void
    {
        $package = $this->updater()->install($this->package());

        $this->assertSame('99.0.0', $package->version());
        $this->assertSame('new', File::get($this->root.'/app/Old.php'));
        $this->assertSame('added', File::get($this->root.'/app/Added.php'));
        // Chránené súbory sa neprepíšu.
        $this->assertSame('APP_KEY=secret', File::get($this->root.'/.env'));
        $this->assertFileDoesNotExist($this->root.'/storage/app/x.txt');
        $this->assertSame('99.0.0', $this->updater()->lastUpdate()['to']);
        $this->assertFalse(app()->isDownForMaintenance());

        $this->updater()->rollback();

        $this->assertSame('old', File::get($this->root.'/app/Old.php'));
        $this->assertFileDoesNotExist($this->root.'/app/Added.php');
        $this->assertDirectoryDoesNotExist($this->root.'/modules');
        $this->assertNull($this->updater()->lastUpdate());
    }

    public function test_failed_migration_rolls_back_automatically(): void
    {
        try {
            $this->updater(fn () => throw new \RuntimeException('Migrácia zlyhala'))->install($this->package());
            $this->fail('Aktualizácia mala zlyhať.');
        } catch (UpdateException $e) {
            $this->assertStringContainsString('Migrácia zlyhala', $e->getMessage());
        }

        $this->assertSame('old', File::get($this->root.'/app/Old.php'));
        $this->assertFileDoesNotExist($this->root.'/app/Added.php');
        $this->assertFalse(app()->isDownForMaintenance());
    }

    public function test_invalid_signature_unsigned_and_older_packages_are_rejected(): void
    {
        // Zmenená verzia po podpísaní.
        $this->assertThrows(fn () => $this->updater()->install($this->package(tamper: '98.0.0')), UpdateException::class, __('admin.updates.signature_failed'));
        $this->assertThrows(fn () => $this->updater()->install($this->package(sign: false)), UpdateException::class, __('admin.updates.unsigned'));
        $this->assertThrows(fn () => $this->updater()->install($this->package('0.0.1')), UpdateException::class);

        $this->assertSame('old', File::get($this->root.'/app/Old.php'));

        // S výslovným súhlasom sa nepodpísaný balík nainštaluje.
        $this->updater()->install($this->package(sign: false), allowUnsigned: true);
        $this->assertSame('new', File::get($this->root.'/app/Old.php'));
    }

    public function test_updates_page_is_only_for_super_admin(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/system/updates')->assertOk()->assertSee(Eshop::VERSION);
    }

    public function test_migrations_of_modules_added_by_the_update_are_run(): void
    {
        User::factory()->create();

        // Stav po nahratí súborov: modul Kontaktné formuláre existuje, ale jeho tabuľky ešte nie.
        Schema::drop('form_submissions');
        Schema::drop('forms');
        DB::table('migrations')->where('migration', 'like', '%create_forms_tables')->delete();

        app(ModuleManager::class)->migrateActive();

        $this->assertTrue(Schema::hasTable('form_submissions'));
        $this->assertSame(1, Form::query()->count());
    }

    public function test_large_update_package_can_be_uploaded_in_admin(): void
    {
        // Livewire predvolene prijme najviac 12 MB, aktualizačný balík má vyše 30 MB.
        $rules = FileUploadConfiguration::rules();
        $this->assertContains('max:'.AppServiceProvider::MAX_UPLOAD_KB, $rules);

        $validator = Validator::make(
            ['files' => [UploadedFile::fake()->create('eshop-update-9.9.9.zip', 40 * 1024, 'application/zip')]],
            ['files.*' => $rules],
        );
        $this->assertTrue($validator->passes());
    }

    /** Odpoveď GitHub API pre najnovšie vydanie s balíkom a jeho SHA-256. */
    private function fakeGithub(string $package, ?string $digest = null): void
    {
        Http::fake([
            'api.github.com/repos/*/releases/latest' => Http::response([
                'tag_name' => 'v99.0.0',
                'body' => 'Novinky',
                'html_url' => 'https://github.com/JojDobre/Eshop/releases/tag/v99.0.0',
                'assets' => [
                    ['name' => 'eshop-1.0.0.zip', 'browser_download_url' => 'https://example.test/full.zip', 'digest' => 'sha256:aa'],
                    ['name' => 'eshop-update-99.0.0.zip', 'browser_download_url' => 'https://example.test/update.zip', 'digest' => 'sha256:'.($digest ?? hash_file('sha256', $package))],
                ],
            ]),
            'example.test/update.zip' => Http::response(File::get($package)),
        ]);
    }

    public function test_checker_reads_latest_github_release(): void
    {
        $this->fakeGithub($this->package());

        $release = app(UpdateChecker::class)->available(true);

        $this->assertSame('99.0.0', $release['version']);
        $this->assertSame('https://example.test/update.zip', $release['download']);
        $this->assertSame(64, strlen($release['sha256']));
        $this->assertSame('99.0.0', app(UpdateChecker::class)->cachedAvailable()['version']);
    }

    public function test_checker_downloads_verifies_and_installs_update(): void
    {
        $this->fakeGithub($this->package());
        $this->app->instance(Updater::class, $this->updater());

        $installed = app(UpdateChecker::class)->installAvailable();

        $this->assertSame('99.0.0', $installed->version());
        $this->assertSame('new', File::get($this->root.'/app/Old.php'));
        $this->assertSame([], File::glob(storage_path('app/private/update-downloads/*.zip')));
    }

    public function test_checker_rejects_package_with_wrong_checksum(): void
    {
        $this->fakeGithub($this->package(), str_repeat('0', 64));
        $this->app->instance(Updater::class, $this->updater());

        try {
            app(UpdateChecker::class)->installAvailable();
            $this->fail('Balík s nesprávnym kontrolným súčtom sa nesmie nainštalovať.');
        } catch (UpdateException) {
            $this->assertSame('old', File::get($this->root.'/app/Old.php'));
        }
    }

    public function test_scheduled_check_notifies_admins_once(): void
    {
        config(['mail.default' => 'array']);
        $this->fakeGithub($this->package());
        $this->superAdmin(['is_active' => true, 'email' => 'boss@example.test']);

        app(UpdateChecker::class)->runScheduled();
        app(UpdateChecker::class)->runScheduled();

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $this->assertStringContainsString('99.0.0', $messages[0]->getOriginalMessage()->getSubject());

        $this->assertSame('99.0.0', app(SystemSettings::class)->updates_notified_version);
        $this->assertSame('old', File::get($this->root.'/app/Old.php'));
    }

    public function test_scheduled_check_installs_when_auto_install_is_enabled(): void
    {
        $this->fakeGithub($this->package());
        $this->app->instance(Updater::class, $this->updater());
        $settings = app(SystemSettings::class);
        $settings->updates_auto_install = true;
        $settings->save();

        app(UpdateChecker::class)->runScheduled();

        $this->assertSame('new', File::get($this->root.'/app/Old.php'));
    }

    public function test_updates_page_shows_available_version(): void
    {
        $this->fakeGithub($this->package());
        $this->actingAs($this->superAdmin(['is_active' => true]))->get('/admin/system/updates')
            ->assertOk()
            ->assertSee(__('admin.updates.available', ['version' => '99.0.0']))
            ->assertSee(__('admin.updates.install_now', ['version' => '99.0.0']));
    }
}
