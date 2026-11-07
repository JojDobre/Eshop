<?php

namespace Tests\Feature;

use App\Core\Backup\BackupManager;
use App\Core\Backup\DatabaseDumper;
use App\Core\System\CronHeartbeat;
use App\Filament\Pages\System\Backups;
use App\Filament\Pages\System\Logs;
use App\Filament\Resources\MediaFiles\Pages\ManageMediaFiles;
use App\Models\MediaFile;
use App\Settings\SystemSettings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PDO;
use ZipArchive;

class SystemTest extends InstalledTestCase
{
    private string $backupDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->backupDir = sys_get_temp_dir().'/eshop-backups-'.uniqid();
        config(['eshop.backup_path' => $this->backupDir]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->backupDir);

        parent::tearDown();
    }

    public function test_database_dump_can_be_restored(): void
    {
        DB::table('users')->insert(['name' => "O'Brien \"Jožko\"", 'email' => 'x@example.com', 'password' => 'x', 'locale' => 'sk', 'is_active' => 1]);
        $sql = tempnam(sys_get_temp_dir(), 'dump');
        app(DatabaseDumper::class)->dump($sql);

        $restored = new PDO('sqlite::memory:');
        $restored->exec((string) file_get_contents($sql));
        unlink($sql);

        $this->assertSame("O'Brien \"Jožko\"", $restored->query("SELECT name FROM users WHERE email = 'x@example.com'")->fetchColumn());
        $this->assertSame(
            DB::table('settings')->count(),
            (int) $restored->query('SELECT COUNT(*) FROM settings')->fetchColumn(),
        );
    }

    public function test_backup_contains_database_and_settings_and_is_pruned(): void
    {
        $settings = app(SystemSettings::class);
        $settings->backups_keep = 2;
        $settings->save();
        $manager = app(BackupManager::class);

        $names = [];
        foreach (range(1, 3) as $i) {
            $this->travel($i)->seconds();
            $names[] = $manager->create(withFiles: false);
        }

        $this->assertCount(2, $manager->all());
        $this->assertSame($names[2], $manager->all()[0]['name']);

        $zip = new ZipArchive;
        $zip->open($manager->path($names[2]));
        $this->assertNotFalse($zip->locateName('database.sql'));
        $this->assertStringContainsString('CREATE TABLE', (string) $zip->getFromName('database.sql'));
        $zip->close();

        $this->expectException(\RuntimeException::class);
        $manager->path('../../.env');
    }

    public function test_admin_backups_page(): void
    {
        $this->actingAs($this->superAdmin());
        $this->get('/admin/system/backups')->assertOk();

        Livewire::test(Backups::class)->callAction('create')->assertNotified();
        $backup = app(BackupManager::class)->all()[0];

        Livewire::test(Backups::class)
            ->assertSee($backup['name'])
            ->callAction('settings', ['backups_enabled' => false, 'backups_include_files' => false, 'backups_keep' => 5]);
        $this->assertFalse(app(SystemSettings::class)->backups_enabled);
        $this->assertSame(5, app(SystemSettings::class)->backups_keep);

        Livewire::test(Backups::class)->callTableAction('delete', $backup['name']);
        $this->assertSame([], app(BackupManager::class)->all());
    }

    public function test_logs_page_shows_entries(): void
    {
        $log = storage_path('logs/test-'.uniqid().'.log');
        File::put($log, "[2026-11-07 10:00:00] testing.ERROR: Platobná brána nedostupná {\"order\":\"1\"}\n#0 stack trace line\n[2026-11-07 10:05:00] testing.INFO: Všetko v poriadku\n");

        try {
            $this->actingAs($this->superAdmin());
            $this->get('/admin/system/logs')->assertOk();

            Livewire::test(Logs::class)
                ->assertSee('Platobná brána nedostupná')
                ->assertSee('Všetko v poriadku')
                ->filterTable('level', 'error')
                ->assertSee('Platobná brána nedostupná')
                ->assertDontSee('Všetko v poriadku');
        } finally {
            File::delete($log);
        }
    }

    public function test_system_status_and_web_cron(): void
    {
        $heartbeat = app(CronHeartbeat::class);
        File::delete($heartbeat->path());
        $this->actingAs($this->superAdmin());

        $this->get('/admin/system/status')->assertOk()->assertSee('schedule:run')->assertSee(CronHeartbeat::webToken());

        $this->get('/cron/'.str_repeat('a', 32))->assertNotFound();
        $this->get('/cron/'.CronHeartbeat::webToken())->assertOk();
        $this->assertTrue($heartbeat->isRunning());

        File::delete($heartbeat->path());
    }

    public function test_media_library_upload_and_delete(): void
    {
        Storage::fake('public');
        $this->actingAs($this->superAdmin());
        $this->get('/admin/media')->assertOk();

        Livewire::test(ManageMediaFiles::class)
            ->callAction('upload', ['files' => [UploadedFile::fake()->image('logo-obchodu.png', 120, 80)]])
            ->assertHasNoActionErrors();

        $file = MediaFile::query()->firstOrFail();
        $this->assertSame('logo-obchodu.png', $file->name);
        $this->assertSame(120, $file->width);
        $this->assertStringStartsWith('image/', (string) $file->mime_type);
        Storage::disk('public')->assertExists($file->path);

        Livewire::test(ManageMediaFiles::class)->callTableAction('delete', $file);
        Storage::disk('public')->assertMissing($file->path);
    }

    public function test_two_factor_authentication_can_be_set_up_in_profile(): void
    {
        $this->actingAs($admin = $this->superAdmin());

        $this->get('/admin/profile')->assertOk()->assertSee('Overovacia aplikácia');

        $admin->saveAppAuthenticationSecret('JBSWY3DPEHPK3PXP');
        $this->assertSame('JBSWY3DPEHPK3PXP', $admin->fresh()->getAppAuthenticationSecret());
        $this->assertNotSame('JBSWY3DPEHPK3PXP', DB::table('users')->where('id', $admin->id)->value('app_authentication_secret'));
    }
}
