<?php

namespace Modules\Transfer;

use App\Core\Installer\Installation;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
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

        // Naplánované exporty posielané e-mailom (webový cron).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->call(fn () => app(ScheduledExports::class)->send())
                ->name('transfer:scheduled-exports')
                ->hourly()
                ->when(fn () => app(Installation::class)->isInstalled());
        });
    }
}
