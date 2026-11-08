<?php

namespace Modules\Sps;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Shipping\Carriers\CarrierRegistry;

class SpsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'sps';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(CarrierRegistry::class)->register($this->app->make(SpsCarrier::class));
    }
}
