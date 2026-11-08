<?php

namespace Modules\Accounting;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Accounting\Exporters\MoneyS3Exporter;
use Modules\Accounting\Exporters\PohodaExporter;

class AccountingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'accounting';
    }

    public function register(): void
    {
        $this->app->singleton(AccountingExporters::class, function () {
            $exporters = new AccountingExporters;
            $exporters->register(new PohodaExporter);
            $exporters->register(new MoneyS3Exporter);

            return $exporters;
        });
    }
}
