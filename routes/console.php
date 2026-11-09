<?php

use App\Core\Backup\BackupRestorer;
use App\Core\Backup\BackupRunner;
use App\Core\Installer\Installation;
use App\Core\Privacy\DataRetention;
use App\Core\Support\EcbRates;
use App\Core\System\CronHeartbeat;
use App\Core\Updates\Updater;
use App\Settings\CurrencySettings;
use App\Settings\SystemSettings;
use Illuminate\Support\Facades\Artisan;
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
        app(BackupRunner::class)->run();
    } catch (Throwable $e) {
        Log::error('Scheduled backup failed.', ['exception' => $e]);
    }
})
    ->name('eshop:backup')
    ->dailyAt('03:10')
    // Týždenná záloha v pondelok, denná každý deň.
    ->when(fn (): bool => $installed() && app(SystemSettings::class)->backups_enabled
        && (app(SystemSettings::class)->backups_frequency !== 'weekly' || now()->isMonday()))
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

// Mazanie a anonymizácia starých osobných údajov podľa nastavených lehôt (GDPR).
Schedule::call(fn () => app(DataRetention::class)->run())
    ->name('eshop:data-retention')
    ->dailyAt('04:20')
    ->when($installed)
    ->withoutOverlapping(60);

// Príkazy pre správcov so SSH (na webhostingu bez SSH slúži administrácia a public/rescue.php).
Artisan::command('eshop:backup {--without-files}', function (): void {
    $result = app(BackupRunner::class)->run(! $this->option('without-files'));
    $this->info($result['name'].': '.$result['check']['message']);
})->purpose('Vytvorí a skontroluje zálohu (a nahrá ju na externé úložisko)');

Artisan::command('eshop:restore {backup} {--without-files}', function (string $backup): void {
    if ($this->confirm(__('admin.backups.restore_warning'), true)) {
        $result = app(BackupRestorer::class)->restore($backup, ! $this->option('without-files'));
        $this->info(__('admin.backups.restored').' ('.$result['statements'].' SQL)');
    }
})->purpose('Obnoví obchod zo zálohy');

Artisan::command('eshop:update {package} {--allow-unsigned}', function (string $package): void {
    $installed = app(Updater::class)->install($package, (bool) $this->option('allow-unsigned'));
    $this->info(__('admin.updates.installed', ['version' => $installed->version()]));
})->purpose('Nainštaluje aktualizačný balík');

Artisan::command('eshop:rollback', function (): void {
    app(Updater::class)->rollback();
    $this->info(__('admin.updates.rolled_back'));
})->purpose('Vráti poslednú aktualizáciu');
