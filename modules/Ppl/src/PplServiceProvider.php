<?php

namespace Modules\Ppl;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Shipping\Carriers\CarrierRegistry;

class PplServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'ppl';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(CarrierRegistry::class)->register($this->app->make(PplCarrier::class));
    }
}
