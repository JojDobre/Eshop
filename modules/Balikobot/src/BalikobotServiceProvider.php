<?php

namespace Modules\Balikobot;

use App\Core\Modules\ModuleServiceProvider;
use Modules\Shipping\Carriers\CarrierRegistry;

class BalikobotServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'balikobot';
    }

    public function boot(): void
    {
        parent::boot();

        $this->app->make(CarrierRegistry::class)->register($this->app->make(BalikobotCarrier::class));
    }
}
