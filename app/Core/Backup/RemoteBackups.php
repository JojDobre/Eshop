<?php

namespace App\Core\Backup;

use App\Settings\SystemSettings;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/**
 * Kópie záloh na externé úložisko: Google Disk (OAuth), FTP, SFTP alebo S3-kompatibilné cez Laravel Filesystem.
 * Záloha na tom istom webhostingu nepomôže, keď sa pokazí celý hosting.
 */
class RemoteBackups
{
    public function __construct(private readonly SystemSettings $settings, private readonly GoogleDrive $drive) {}

    public function enabled(): bool
    {
        if ($this->usesGoogleDrive()) {
            return $this->drive->isConnected();
        }

        return in_array($this->settings->backups_remote, ['ftp', 'sftp', 's3'], true) && filled($this->settings->backups_remote_host ?: $this->settings->backups_remote_bucket);
    }

    /**
     * Nahrá zálohu a na úložisku ponechá iba posledných N záloh.
     */
    public function upload(string $localPath): void
    {
        if ($this->usesGoogleDrive()) {
            $this->drive->upload($localPath);

            foreach (array_slice($this->driveBackups(), max(1, $this->settings->backups_remote_keep)) as $old) {
                $this->drive->delete($old['id']);
            }

            return;
        }

        $disk = $this->disk();
        $handle = fopen($localPath, 'rb');

        try {
            if (! $disk->writeStream(basename($localPath), $handle)) {
                throw new BackupException(__('admin.backups.remote_failed'));
            }
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        $this->prune($disk);
    }

    /**
     * Overí pripojenie zápisom a zmazaním malého súboru.
     */
    public function test(): void
    {
        if ($this->usesGoogleDrive()) {
            // Zoznam súborov overí token aj prístup k priečinku (a priečinok prípadne vytvorí).
            $this->drive->files();

            return;
        }

        $disk = $this->disk();
        $file = 'eshop-connection-test.txt';

        if (! $disk->put($file, 'ok') || $disk->get($file) !== 'ok') {
            throw new BackupException(__('admin.backups.remote_failed'));
        }

        $disk->delete($file);
    }

    /**
     * @return list<string>
     */
    public function files(): array
    {
        if ($this->usesGoogleDrive()) {
            return array_column($this->driveBackups(), 'name');
        }

        return collect($this->disk()->files())
            ->filter(fn (string $file) => (bool) preg_match('/^eshop-[0-9-]+\.zip$/', basename($file)))
            ->sortDesc()
            ->values()
            ->all();
    }

    public function disk(): Filesystem
    {
        $s = $this->settings;
        $root = trim((string) $s->backups_remote_root, '/');

        $config = match ($s->backups_remote) {
            'ftp' => ['driver' => 'ftp', 'host' => $s->backups_remote_host, 'port' => $s->backups_remote_port ?: 21, 'username' => $s->backups_remote_username,
                'password' => $s->backups_remote_password, 'root' => $root, 'passive' => true, 'ssl' => false, 'timeout' => 30],
            'sftp' => ['driver' => 'sftp', 'host' => $s->backups_remote_host, 'port' => $s->backups_remote_port ?: 22, 'username' => $s->backups_remote_username,
                'password' => $s->backups_remote_password, 'root' => $root === '' ? '' : '/'.$root, 'timeout' => 30],
            's3' => array_filter(['driver' => 's3', 'key' => $s->backups_remote_username, 'secret' => $s->backups_remote_password, 'region' => $s->backups_remote_region ?: 'eu-central-1',
                'bucket' => $s->backups_remote_bucket, 'endpoint' => $s->backups_remote_endpoint ?: null, 'use_path_style_endpoint' => filled($s->backups_remote_endpoint), 'root' => $root, 'throw' => true]),
            default => throw new BackupException(__('admin.backups.remote_disabled')),
        };

        return Storage::build($config);
    }

    public function usesGoogleDrive(): bool
    {
        return $this->settings->backups_remote === 'gdrive';
    }

    /**
     * @return list<array{id: string, name: string, size: int}>
     */
    private function driveBackups(): array
    {
        return array_values(array_filter($this->drive->files(), fn (array $file) => (bool) preg_match('/^eshop-[0-9-]+\.zip$/', $file['name'])));
    }

    private function prune(Filesystem $disk): void
    {
        foreach (array_slice($this->files(), max(1, $this->settings->backups_remote_keep)) as $old) {
            $disk->delete($old);
        }
    }
}
