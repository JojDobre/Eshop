<?php

use App\Core\Backup\BackupManager;
use App\Core\Installer\Installation;
use App\Core\System\CronHeartbeat;
use App\Settings\SystemSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

/*
| Plánované úlohy. Na hostingu stačí cron „php artisan schedule:run“ každú minútu
| alebo webový cron (Systém → Stav systému).
*/

$installed = fn (): bool => app(Installation::class)->isInstalled();

Schedule::call(fn () => app(CronHeartbeat::class)->record())
    ->name('eshop:heartbeat')
    ->everyMinute()
    ->when($installed);

Schedule::call(function (): void {
    try {
        app(BackupManager::class)->create(app(SystemSettings::class)->backups_include_files);
    } catch (Throwable $e) {
        Log::error('Scheduled backup failed.', ['exception' => $e]);
    }
})
    ->name('eshop:backup')
    ->dailyAt('03:10')
    ->when(fn (): bool => $installed() && app(SystemSettings::class)->backups_enabled)
    ->withoutOverlapping(120);
