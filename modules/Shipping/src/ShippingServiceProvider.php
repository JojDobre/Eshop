<?php

namespace Modules\Shipping;

use App\Core\Events\EshopInstalled;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Shipping\Listeners\CreateDefaultShippingMethods;

class ShippingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'shipping';
    }

    public function boot(): void
    {
        parent::boot();

        Event::listen(EshopInstalled::class, CreateDefaultShippingMethods::class);
    }
}
