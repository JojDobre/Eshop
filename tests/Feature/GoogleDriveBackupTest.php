<?php

namespace Tests\Feature;

use App\Core\Backup\GoogleDrive;
use App\Core\Backup\RemoteBackups;
use App\Http\Controllers\Admin\GoogleDriveCallbackController;
use App\Settings\SystemSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class GoogleDriveBackupTest extends InstalledTestCase
{
    private function configure(bool $connected = true): SystemSettings
    {
        $settings = app(SystemSettings::class);
        $settings->backups_remote = 'gdrive';
        $settings->backups_remote_keep = 2;
        $settings->backups_remote_root = 'zalohy-obchodu';
        $settings->backups_gdrive_client_id = 'client-id';
        $settings->backups_gdrive_client_secret = 'client-secret';
        $settings->backups_gdrive_refresh_token = $connected ? 'refresh-token' : null;
        $settings->backups_gdrive_folder_id = null;
        $settings->save();

        return $settings;
    }

    private function fakeDrive(array $existing = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access', 'expires_in' => 3600, 'refresh_token' => 'new-refresh']),
            'www.googleapis.com/upload/drive/v3/files*' => Http::response([], 200, ['Location' => 'https://www.googleapis.com/upload/session/abc']),
            'www.googleapis.com/upload/session/abc' => Http::response(['id' => 'uploaded-id']),
            'www.googleapis.com/drive/v3/about*' => Http::response(['user' => ['emailAddress' => 'shop@example.test']]),
            'www.googleapis.com/drive/v3/files/*' => Http::response([], 204),
            'www.googleapis.com/drive/v3/files?fields=id' => Http::response(['id' => 'folder-1']),
            'www.googleapis.com/drive/v3/files?*' => Http::response(['files' => $existing]),
        ]);
    }

    public function test_backup_is_uploaded_in_chunks_and_old_copies_are_pruned(): void
    {
        $this->configure();
        $this->fakeDrive([
            ['id' => 'n3', 'name' => 'eshop-2026-12-03-031000.zip', 'size' => '10'],
            ['id' => 'n2', 'name' => 'eshop-2026-12-02-031000.zip', 'size' => '10'],
            ['id' => 'n1', 'name' => 'eshop-2026-12-01-031000.zip', 'size' => '10'],
            ['id' => 'x', 'name' => 'iny-subor.txt', 'size' => '1'],
        ]);
        $file = sys_get_temp_dir().'/eshop-2026-12-04-031000.zip';
        File::put($file, str_repeat('a', 1000));

        $remote = app(RemoteBackups::class);
        $this->assertTrue($remote->enabled());
        $remote->upload($file);
        File::delete($file);

        Http::assertSent(fn (Request $request) => $request->url() === 'https://www.googleapis.com/drive/v3/files?fields=id'
            && $request['name'] === 'zalohy-obchodu' && $request['mimeType'] === 'application/vnd.google-apps.folder');
        Http::assertSent(fn (Request $request) => str_contains($request->url(), 'uploadType=resumable')
            && $request['parents'] === ['folder-1'] && $request['name'] === 'eshop-2026-12-04-031000.zip');
        Http::assertSent(fn (Request $request) => $request->url() === 'https://www.googleapis.com/upload/session/abc'
            && $request->header('Content-Range')[0] === 'bytes 0-999/1000' && $request->hasHeader('Authorization', 'Bearer access'));
        // Ponechajú sa 2 najnovšie zálohy, cudzí súbor sa nemaže.
        Http::assertSent(fn (Request $request) => $request->method() === 'DELETE' && str_ends_with($request->url(), '/files/n1'));
        Http::assertNotSent(fn (Request $request) => $request->method() === 'DELETE' && (str_ends_with($request->url(), '/files/x') || str_ends_with($request->url(), '/files/n2')));
        $this->assertSame('folder-1', app(SystemSettings::class)->backups_gdrive_folder_id);
    }

    public function test_remote_is_disabled_until_google_account_is_connected(): void
    {
        $this->configure(connected: false);

        $this->assertFalse(app(RemoteBackups::class)->enabled());
        $this->assertStringContainsString('access_type=offline', app(GoogleDrive::class)->authorizationUrl('s'));
        $this->assertStringContainsString(urlencode(GoogleDrive::SCOPE), app(GoogleDrive::class)->authorizationUrl('s'));
    }

    public function test_callback_connects_account_with_valid_state(): void
    {
        $this->configure(connected: false);
        $this->fakeDrive();

        $this->actingAs($this->superAdmin(['is_active' => true]))
            ->withSession([GoogleDriveCallbackController::STATE_KEY => 'state-123'])
            ->get('/admin/backups/google/callback?state=state-123&code=auth-code')
            ->assertRedirect('/admin/system/backups');

        $settings = app(SystemSettings::class)->refresh();
        $this->assertSame('new-refresh', $settings->backups_gdrive_refresh_token);
        $this->assertSame('shop@example.test', $settings->backups_gdrive_account);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://oauth2.googleapis.com/token' && $request['code'] === 'auth-code');
    }

    public function test_callback_rejects_invalid_state(): void
    {
        $this->configure(connected: false);
        $this->fakeDrive();

        $this->actingAs($this->superAdmin(['is_active' => true]))
            ->withSession([GoogleDriveCallbackController::STATE_KEY => 'state-123'])
            ->get('/admin/backups/google/callback?state=other&code=auth-code')
            ->assertRedirect('/admin/system/backups');

        $this->assertNull(app(SystemSettings::class)->refresh()->backups_gdrive_refresh_token);
        Http::assertNothingSent();
    }

    public function test_backups_page_shows_google_drive_actions(): void
    {
        $this->configure();

        $this->actingAs($this->superAdmin(['is_active' => true]))->get('/admin/system/backups')
            ->assertOk()
            ->assertSee(__('admin.backups.gdrive_reconnect'))
            ->assertSee(__('admin.backups.gdrive_disconnect'));
    }
}
