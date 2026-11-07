<?php

use App\Core\Backup\BackupManager;
use App\Core\Installer\Installation;
use App\Core\Support\EcbRates;
use App\Core\System\CronHeartbeat;
use App\Settings\CurrencySettings;
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

// Kurzy mien z ECB (zverejňuje ich okolo 16:00 SEČ v pracovné dni).
Schedule::call(function (): void {
    try {
        app(EcbRates::class)->update();
    } catch (Throwable $e) {
        Log::warning('ECB rates update failed.', ['exception' => $e]);
    }
})
    ->name('eshop:currency-rates')
    ->dailyAt('16:20')
    ->timezone('Europe/Bratislava')
    ->when(function () use ($installed): bool {
        if (! $installed()) {
            return false;
        }

        $settings = app(CurrencySettings::class);

        return $settings->auto_update && $settings->enabled !== [];
    });
