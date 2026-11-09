<?php

namespace Modules\Transfer;

use App\Core\Installer\Installation;
use App\Core\Modules\ModuleServiceProvider;
use App\Core\Privacy\DataRetention;
use App\Core\Privacy\RetentionPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Modules\Transfer\Exports\Exports;
use Modules\Transfer\Imports\CustomerImporter;
use Modules\Transfer\Imports\Importers;
use Modules\Transfer\Imports\ProductImporter;

class TransferServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'transfer';
    }

    public function register(): void
    {
        $this->app->singleton(Exports::class, function () {
            $exports = new Exports;
            $exports->registerDefaults();

            return $exports;
        });
        $this->app->singleton(Importers::class, function () {
            $importers = new Importers;
            $importers->register(new ProductImporter);
            $importers->register(new CustomerImporter);

            return $importers;
        });
    }

    public function boot(): void
    {
        parent::boot();

        // Exportované súbory (môžu obsahovať osobné údaje zákazníkov) sa po čase zmažú.
        $this->callAfterResolving(DataRetention::class, fn (DataRetention $retention) => $retention->register(new RetentionPolicy(
            'export_files', fn () => __('admin.privacy.policies.export_files'), fn () => __('admin.privacy.policies.export_files_help'), 7, true,
            function (Carbon $before): int {
                $deleted = 0;

                foreach (array_merge(glob(storage_path('app/private/exports/*')) ?: [], glob(storage_path('app/private/imports/*')) ?: []) as $file) {
                    if (is_file($file) && filemtime($file) < $before->getTimestamp()) {
                        unlink($file);
                        $deleted++;
                    }
                }

                return $deleted;
            },
        )));

        // Naplánované exporty posielané e-mailom (webový cron).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->call(fn () => app(ScheduledExports::class)->send())
                ->name('transfer:scheduled-exports')
                ->hourly()
                ->when(fn () => app(Installation::class)->isInstalled());
        });
    }
}
