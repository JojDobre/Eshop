<?php

namespace App\Core\Updates;

use App\Core\Eshop;
use App\Models\User;
use App\Settings\SystemSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * Automatické aktualizácie: zistí najnovšie vydanie na GitHube (alebo vo vlastnom kanáli), stiahne
 * aktualizačný balík, overí jeho kontrolný súčet a nainštaluje ho cez Updater (záloha, údržba, vrátenie pri chybe).
 */
class UpdateChecker
{
    public const CACHE_KEY = 'eshop-update-latest';

    /**
     * Najnovšia dostupná verzia (alebo null, ak sa nedá zistiť).
     *
     * @return array{version: string, notes: ?string, url: ?string, download: ?string, sha256: ?string, published_at: ?string}|null
     */
    public function latest(bool $fresh = false): ?array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, now()->addHours(6), fn () => $this->fetch());
    }

    /** Novšia verzia, než je nainštalovaná (alebo null). */
    public function available(bool $fresh = false): ?array
    {
        $latest = $this->latest($fresh);

        return $latest !== null && version_compare($latest['version'], Eshop::VERSION, '>') ? $latest : null;
    }

    /** Novšia verzia z vyrovnávacej pamäte bez sieťovej požiadavky (na odznak v menu). */
    public function cachedAvailable(): ?array
    {
        $latest = Cache::get(self::CACHE_KEY);

        return is_array($latest) && version_compare((string) ($latest['version'] ?? '0'), Eshop::VERSION, '>') ? $latest : null;
    }

    /**
     * Stiahne a nainštaluje dostupnú aktualizáciu.
     */
    public function installAvailable(): UpdatePackage
    {
        $release = $this->available(true);

        if ($release === null || blank($release['download'])) {
            throw new UpdateException(__('admin.updates.up_to_date'));
        }

        $path = $this->download($release);

        try {
            // Balík bez podpisu sa nainštaluje iba vtedy, keď obchod podpisy neoveruje a kontrolný súčet
            // pochádza priamo z GitHubu cez HTTPS.
            return app(Updater::class)->install($path, allowUnsigned: Updater::publicKeys() === [] && filled($release['sha256']));
        } finally {
            File::delete($path);
            Cache::forget(self::CACHE_KEY);
        }
    }

    /**
     * Stiahne balík do dočasného priečinka a overí jeho SHA-256.
     */
    public function download(array $release): string
    {
        $directory = storage_path('app/private/update-downloads');
        File::ensureDirectoryExists($directory);
        $path = $directory.'/eshop-update-'.Str::slug($release['version']).'-'.Str::random(6).'.zip';

        try {
            Http::timeout(300)->withOptions(['sink' => $path])->get((string) $release['download'])->throw();
        } catch (Throwable $e) {
            File::delete($path);

            throw new UpdateException(__('admin.updates.download_failed', ['message' => $e->getMessage()]), previous: $e);
        }

        if (filled($release['sha256']) && ! hash_equals(strtolower((string) $release['sha256']), (string) hash_file('sha256', $path))) {
            File::delete($path);

            throw new UpdateException(__('admin.updates.checksum_failed'));
        }

        return $path;
    }

    /**
     * Denná úloha: upozorní správcov na novú verziu (raz) a pri zapnutej automatickej inštalácii ju nainštaluje.
     */
    public function runScheduled(): void
    {
        $settings = app(SystemSettings::class);
        $release = $this->available(true);

        if ($release === null) {
            return;
        }

        if ($settings->updates_auto_install) {
            try {
                $package = $this->installAvailable();
                $this->notifyAdmins(__('admin.updates.mail_installed_subject', ['version' => $package->version()]),
                    __('admin.updates.mail_installed_body', ['version' => $package->version(), 'url' => url('/admin/system/updates')]));
            } catch (Throwable $e) {
                Log::error('Automatic update failed.', ['exception' => $e, 'version' => $release['version']]);
                $this->notifyAdmins(__('admin.updates.mail_failed_subject', ['version' => $release['version']]),
                    __('admin.updates.mail_failed_body', ['version' => $release['version'], 'message' => $e->getMessage(), 'url' => url('/admin/system/updates')]));
            }

            return;
        }

        if ($settings->updates_notify && $settings->updates_notified_version !== $release['version']) {
            $this->notifyAdmins(__('admin.updates.mail_available_subject', ['version' => $release['version']]),
                __('admin.updates.mail_available_body', ['version' => $release['version'], 'notes' => (string) $release['notes'], 'url' => url('/admin/system/updates')]));
            $settings->updates_notified_version = $release['version'];
            $settings->save();
        }
    }

    private function notifyAdmins(string $subject, string $body): void
    {
        User::query()->where('is_active', true)->get()
            ->filter(fn (User $user) => $user->isSuperAdmin() && filled($user->email))
            ->each(function (User $user) use ($subject, $body): void {
                rescue(fn () => Mail::raw($body, fn ($message) => $message->to($user->email)->subject($subject)), report: true);
            });
    }

    /**
     * @return array{version: string, notes: ?string, url: ?string, download: ?string, sha256: ?string, published_at: ?string}|null
     */
    private function fetch(): ?array
    {
        try {
            if (filled($feed = config('eshop.updates.feed_url'))) {
                $data = Http::timeout(10)->acceptJson()->get((string) $feed)->throw()->json();

                return is_array($data) && filled($data['version'] ?? null) ? [
                    'version' => ltrim((string) $data['version'], 'v'),
                    'notes' => $data['notes'] ?? null,
                    'url' => $data['url'] ?? null,
                    'download' => $data['download'] ?? $data['url'] ?? null,
                    'sha256' => $data['sha256'] ?? null,
                    'published_at' => $data['published_at'] ?? null,
                ] : null;
            }

            $repository = (string) config('eshop.updates.github_repo');

            if ($repository === '') {
                return null;
            }

            $data = Http::timeout(10)->acceptJson()->withHeaders(['User-Agent' => 'eshop-updater'])
                ->get("https://api.github.com/repos/{$repository}/releases/latest")->throw()->json();
        } catch (Throwable $e) {
            Log::warning('Update check failed.', ['exception' => $e]);

            return null;
        }

        $asset = collect($data['assets'] ?? [])->first(fn ($asset) => preg_match('/^eshop-update-.+\.zip$/', (string) ($asset['name'] ?? '')) === 1);

        return [
            'version' => ltrim((string) ($data['tag_name'] ?? '0'), 'v'),
            'notes' => $data['body'] ?? null,
            'url' => $data['html_url'] ?? null,
            'download' => $asset['browser_download_url'] ?? null,
            'sha256' => isset($asset['digest']) && str_starts_with((string) $asset['digest'], 'sha256:') ? substr((string) $asset['digest'], 7) : null,
            'published_at' => $data['published_at'] ?? null,
        ];
    }
}
