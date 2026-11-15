<?php

namespace App\Core\Backup;

use App\Settings\SystemSettings;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Zálohy na Google Disk cez Drive API v3 a OAuth 2.0 (bez ďalších balíkov).
 *
 * Správca si v Google Cloud Console vytvorí OAuth klienta typu „Webová aplikácia“, vloží Client ID a Client Secret
 * a prepojí účet. Rozsah drive.file dovoľuje obchodu pracovať iba so súbormi, ktoré sám vytvoril.
 */
class GoogleDrive
{
    public const SCOPE = 'https://www.googleapis.com/auth/drive.file';

    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const API = 'https://www.googleapis.com/drive/v3';

    private const UPLOAD_API = 'https://www.googleapis.com/upload/drive/v3';

    /** Veľkosť časti pri nahrávaní (násobok 256 KB podľa požiadaviek Drive API). */
    public const CHUNK_SIZE = 8 * 1024 * 1024;

    public function __construct(private readonly SystemSettings $settings) {}

    public static function redirectUri(): string
    {
        return route('filament.admin.backups.google.callback');
    }

    public function hasCredentials(): bool
    {
        return filled($this->settings->backups_gdrive_client_id) && filled($this->settings->backups_gdrive_client_secret);
    }

    public function isConnected(): bool
    {
        return $this->hasCredentials() && filled($this->settings->backups_gdrive_refresh_token);
    }

    /**
     * Adresa na prihlásenie do Google účtu a udelenie súhlasu.
     */
    public function authorizationUrl(string $state): string
    {
        return self::AUTH_URL.'?'.http_build_query([
            'client_id' => $this->settings->backups_gdrive_client_id,
            'redirect_uri' => self::redirectUri(),
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            // Súhlas vždy znova, aby Google vrátil obnovovací token.
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => $state,
        ]);
    }

    /**
     * Vymení autorizačný kód za obnovovací token a uloží ho (šifrovane).
     */
    public function connect(string $code): void
    {
        $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => $this->settings->backups_gdrive_client_id,
            'client_secret' => $this->settings->backups_gdrive_client_secret,
            'redirect_uri' => self::redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        $token = $response->json('refresh_token');

        if ($response->failed() || blank($token)) {
            throw new BackupException(__('admin.backups.gdrive_connect_failed', ['message' => $this->error($response)]));
        }

        $this->settings->backups_gdrive_refresh_token = $token;
        Cache::put($this->tokenCacheKey(), (string) $response->json('access_token'), now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 120)));

        $this->settings->backups_gdrive_folder_id = null;
        $this->settings->backups_gdrive_account = rescue(fn () => $this->api()->get(self::API.'/about', ['fields' => 'user(emailAddress)'])->json('user.emailAddress'), report: false);
        $this->settings->save();
    }

    public function disconnect(): void
    {
        $token = $this->settings->backups_gdrive_refresh_token;

        if (filled($token)) {
            rescue(fn () => Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/revoke', ['token' => $token]), report: false);
        }

        Cache::forget($this->tokenCacheKey());
        $this->settings->backups_gdrive_refresh_token = null;
        $this->settings->backups_gdrive_folder_id = null;
        $this->settings->backups_gdrive_account = null;
        $this->settings->save();
    }

    /**
     * Nahrá súbor (po častiach, aby sa zmestil aj veľký archív do pamäte lacného hostingu).
     */
    public function upload(string $localPath, ?string $name = null): string
    {
        $size = (int) filesize($localPath);
        $start = $this->api()->withHeaders([
            'X-Upload-Content-Type' => 'application/zip',
            'X-Upload-Content-Length' => (string) $size,
        ])->post(self::UPLOAD_API.'/files?uploadType=resumable&fields=id', [
            'name' => $name ?? basename($localPath),
            'parents' => [$this->folderId()],
        ]);

        $session = $start->header('Location');

        if ($start->failed() || blank($session)) {
            throw new BackupException(__('admin.backups.gdrive_failed', ['message' => $this->error($start)]));
        }

        $handle = fopen($localPath, 'rb');
        $offset = 0;

        try {
            do {
                $chunk = (string) fread($handle, self::CHUNK_SIZE);
                $length = strlen($chunk);
                $range = $size === 0 ? 'bytes */0' : sprintf('bytes %d-%d/%d', $offset, $offset + $length - 1, $size);

                $response = Http::timeout(300)->withToken($this->accessToken())
                    ->withHeaders(['Content-Range' => $range])
                    ->withBody($chunk, 'application/zip')
                    ->put($session);

                $offset += $length;

                // 308 = časť prijatá, pokračuje sa ďalšou; 200/201 = súbor je kompletný.
                if ($response->status() !== 308 && $response->failed()) {
                    throw new BackupException(__('admin.backups.gdrive_failed', ['message' => $this->error($response)]));
                }
            } while ($response->status() === 308 && $offset < $size);
        } finally {
            fclose($handle);
        }

        return (string) $response->json('id');
    }

    /**
     * Zálohy v priečinku obchodu, od najnovšej.
     *
     * @return list<array{id: string, name: string, size: int}>
     */
    public function files(): array
    {
        $response = $this->api()->get(self::API.'/files', [
            'q' => sprintf("'%s' in parents and trashed = false", $this->folderId()),
            'fields' => 'files(id,name,size)',
            'orderBy' => 'name desc',
            'pageSize' => 1000,
        ]);

        if ($response->failed()) {
            throw new BackupException(__('admin.backups.gdrive_failed', ['message' => $this->error($response)]));
        }

        return collect($response->json('files', []))
            ->map(fn (array $file) => ['id' => (string) $file['id'], 'name' => (string) $file['name'], 'size' => (int) ($file['size'] ?? 0)])
            ->values()
            ->all();
    }

    public function delete(string $id): void
    {
        $response = $this->api()->delete(self::API.'/files/'.rawurlencode($id));

        if ($response->failed() && $response->status() !== 404) {
            throw new BackupException(__('admin.backups.gdrive_failed', ['message' => $this->error($response)]));
        }
    }

    /**
     * Stiahne súbor z Disku (napr. na obnovu).
     */
    public function download(string $id, string $target): void
    {
        $this->api()->timeout(600)->withOptions(['sink' => $target])->get(self::API.'/files/'.rawurlencode($id), ['alt' => 'media'])->throw();
    }

    /**
     * Priečinok so zálohami (vytvorí sa pri prvom použití podľa nastaveného názvu).
     */
    public function folderId(): string
    {
        if (filled($this->settings->backups_gdrive_folder_id)) {
            return $this->settings->backups_gdrive_folder_id;
        }

        $name = trim((string) $this->settings->backups_remote_root, '/') ?: 'eshop-zalohy';
        $response = $this->api()->post(self::API.'/files?fields=id', [
            'name' => Str::limit($name, 100, ''),
            'mimeType' => 'application/vnd.google-apps.folder',
        ]);

        if ($response->failed() || blank($response->json('id'))) {
            throw new BackupException(__('admin.backups.gdrive_failed', ['message' => $this->error($response)]));
        }

        $this->settings->backups_gdrive_folder_id = (string) $response->json('id');
        $this->settings->save();

        return $this->settings->backups_gdrive_folder_id;
    }

    private function api(): PendingRequest
    {
        return Http::timeout(30)->acceptJson()->withToken($this->accessToken());
    }

    /**
     * Krátkodobý prístupový token získaný z obnovovacieho tokenu (uchovaný v cache takmer hodinu).
     */
    private function accessToken(): string
    {
        if (! $this->isConnected()) {
            throw new BackupException(__('admin.backups.gdrive_not_connected'));
        }

        if ($token = Cache::get($this->tokenCacheKey())) {
            return $token;
        }

        $response = Http::asForm()->timeout(20)->post(self::TOKEN_URL, [
            'client_id' => $this->settings->backups_gdrive_client_id,
            'client_secret' => $this->settings->backups_gdrive_client_secret,
            'refresh_token' => $this->settings->backups_gdrive_refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed() || blank($response->json('access_token'))) {
            throw new BackupException(__('admin.backups.gdrive_token_failed', ['message' => $this->error($response)]));
        }

        $token = (string) $response->json('access_token');
        Cache::put($this->tokenCacheKey(), $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 120)));

        return $token;
    }

    private function tokenCacheKey(): string
    {
        return 'gdrive-access-token-'.substr(hash('sha256', (string) $this->settings->backups_gdrive_refresh_token), 0, 16);
    }

    private function error(Response $response): string
    {
        $message = $response->json('error_description') ?? $response->json('error.message') ?? $response->json('error');

        return is_string($message) && $message !== '' ? $message : 'HTTP '.$response->status();
    }
}
