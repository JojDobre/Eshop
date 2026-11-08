<?php

namespace Modules\Dpd;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Shipping\Carriers\CarrierRegistry;

class DpdServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'dpd';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(CarrierRegistry::class)->register(new DpdCarrier);
    }
}
