<?php

namespace Tests\Feature;

use App\Core\Backup\BackupManager;
use App\Core\Backup\BackupRestorer;
use App\Core\Backup\BackupRunner;
use App\Core\Backup\BackupVerifier;
use App\Core\Backup\RemoteBackups;
use App\Core\Backup\SqlImporter;
use App\Models\User;
use App\Settings\SystemSettings;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class BackupRestoreTest extends InstalledTestCase
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

    public function test_sql_importer_handles_semicolons_and_quotes_in_values(): void
    {
        DB::statement('CREATE TABLE import_test (id INTEGER, note TEXT)');
        $sql = "INSERT INTO import_test (id, note) VALUES (1, 'a; b'), (2, 'it''s;\nnew line');\nINSERT INTO import_test (id, note) VALUES (3, \"x\");";
        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $sql);
        rewind($stream);

        $this->assertSame(2, app(SqlImporter::class)->import($stream));
        $this->assertSame(['a; b', "it's;\nnew line", 'x'], DB::table('import_test')->orderBy('id')->pluck('note')->all());
    }

    public function test_encrypted_backup_is_verified_restored_and_copied_to_remote_storage(): void
    {
        $settings = app(SystemSettings::class);
        $settings->backups_password = 'tajne-heslo';
        $settings->backups_remote = 'sftp';
        $settings->backups_remote_host = 'zalohy.example.com';
        $settings->save();

        $remote = Storage::fake('remote');
        $this->app->bind(RemoteBackups::class, fn ($app) => new class($app->make(SystemSettings::class), $remote) extends RemoteBackups
        {
            public function __construct(SystemSettings $settings, private Filesystem $fake)
            {
                parent::__construct($settings);
            }

            public function disk(): Filesystem
            {
                return $this->fake;
            }
        });

        User::factory()->create(['email' => 'pred@zalohou.sk']);
        $result = app(BackupRunner::class)->run(withFiles: false);

        $this->assertTrue($result['check']['ok'], $result['check']['message']);
        $this->assertTrue($result['remote']);
        $remote->assertExists($result['name']);
        $this->assertTrue(app(SystemSettings::class)->backups_last_check['ok']);

        // Bez hesla sa šifrovaná záloha neprečíta.
        $zip = new ZipArchive;
        $zip->open(app(BackupManager::class)->path($result['name']));
        $this->assertFalse($zip->getFromName('database.sql'));
        $zip->close();
        $this->assertFalse(app(BackupVerifier::class)->verify($result['name'], 'zle-heslo')['ok']);

        // Zmena po záloh sa obnovou vráti.
        User::query()->where('email', 'pred@zalohou.sk')->delete();
        User::factory()->create(['email' => 'po@zalohe.sk']);

        $restored = app(BackupRestorer::class)->restore($result['name'], withFiles: false);

        $this->assertGreaterThan(10, $restored['statements']);
        $this->assertNotNull($restored['safety_backup']);
        $this->assertTrue(User::query()->where('email', 'pred@zalohou.sk')->exists());
        $this->assertFalse(User::query()->where('email', 'po@zalohe.sk')->exists());
    }

    public function test_damaged_backup_fails_the_check(): void
    {
        File::ensureDirectoryExists($this->backupDir);
        $zip = new ZipArchive;
        $zip->open($this->backupDir.'/eshop-2026-01-01-000000.zip', ZipArchive::CREATE);
        $zip->addFromString('database.sql', "CREATE TABLE `orders` (id int);\n");
        $zip->close();

        $result = app(BackupVerifier::class)->verify('eshop-2026-01-01-000000.zip');

        $this->assertFalse($result['ok']);
        $this->assertStringContainsString('users', $result['message']);
    }
}
