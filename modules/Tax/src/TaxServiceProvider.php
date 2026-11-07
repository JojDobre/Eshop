<?php

namespace Modules\Tax;

use App\Core\Events\EshopInstalled;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Tax\Listeners\CreateDefaultTaxRates;

class TaxServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'tax';
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(EshopInstalled::class, CreateDefaultTaxRates::class);
    }
}
