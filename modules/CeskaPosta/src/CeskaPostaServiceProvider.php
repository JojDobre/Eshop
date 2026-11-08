<?php

namespace Modules\CeskaPosta;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Shipping\Carriers\CarrierRegistry;

class CeskaPostaServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'ceskaposta';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(CarrierRegistry::class)->register($this->app->make(CeskaPostaCarrier::class));
    }
}
