<?php

namespace Modules\Gls;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Shipping\Carriers\CarrierRegistry;

class GlsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'gls';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(CarrierRegistry::class)->register($this->app->make(GlsCarrier::class));
    }
}
