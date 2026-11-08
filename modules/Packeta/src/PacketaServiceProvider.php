<?php

namespace Modules\Packeta;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Shipping\Carriers\CarrierRegistry;

class PacketaServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'packeta';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(CarrierRegistry::class)->register($this->app->make(PacketaCarrier::class));
    }
}
