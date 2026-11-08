<?php

namespace Modules\SlovenskaPosta;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Shipping\Carriers\CarrierRegistry;

class SlovenskaPostaServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'slovenskaposta';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(CarrierRegistry::class)->register($this->app->make(SlovenskaPostaCarrier::class));
    }
}
