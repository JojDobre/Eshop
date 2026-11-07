<?php

namespace App\Core\System;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;

/**
 * Záznam posledného spustenia plánovača (overenie, či cron na hostingu beží).
 */
class CronHeartbeat
{
    public function path(): string
    {
        return storage_path('app/eshop/cron.json');
    }

    public function record(): void
    {
        File::ensureDirectoryExists(dirname($this->path()));
        File::put($this->path(), json_encode(['last_run' => now()->toIso8601String()]));
    }

    public function lastRun(): ?Carbon
    {
        $data = is_file($this->path()) ? json_decode((string) file_get_contents($this->path()), true) : null;

        return isset($data['last_run']) ? Carbon::parse($data['last_run']) : null;
    }

    /** Beží cron (posledné spustenie za posledných 15 minút)? */
    public function isRunning(): bool
    {
        return $this->lastRun()?->greaterThan(now()->subMinutes(15)) ?? false;
    }

    /**
     * Tajný kľúč webového cronu pre hostingy bez plánovača (odvodený od APP_KEY).
     */
    public static function webToken(): string
    {
        return substr(hash_hmac('sha256', 'eshop-cron', (string) config('app.key')), 0, 32);
    }
}
