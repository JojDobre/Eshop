<?php

namespace App\Core\Backup;

use App\Settings\SystemSettings;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Celý postup zálohy: vytvorenie, test obnovy a kópia na externé úložisko.
 * Používa ho plánovaná záloha aj tlačidlo v administrácii.
 */
class BackupRunner
{
    public function __construct(
        private readonly BackupManager $backups,
        private readonly BackupVerifier $verifier,
        private readonly RemoteBackups $remote,
    ) {}

    /**
     * @return array{name: string, check: array{ok: bool, message: string}, remote: ?bool}
     */
    public function run(?bool $withFiles = null): array
    {
        $name = $this->backups->create($withFiles ?? app(SystemSettings::class)->backups_include_files);
        $check = $this->verifier->verify($name);
        $remote = null;

        if ($this->remote->enabled()) {
            try {
                $this->remote->upload($this->backups->path($name));
                $remote = true;
            } catch (Throwable $e) {
                Log::error('Remote backup upload failed.', ['exception' => $e]);
                $remote = false;
            }
        }

        if (! $check['ok']) {
            Log::critical('Backup check failed: '.$check['message'], ['backup' => $name]);
        }

        return ['name' => $name, 'check' => $check, 'remote' => $remote];
    }
}
