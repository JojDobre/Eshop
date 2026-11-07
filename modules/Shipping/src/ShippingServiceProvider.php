<?php

namespace Modules\Shipping;

use App\Core\Content\TranslatableModels;
use App\Core\Events\EshopInstalled;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Support\Facades\Event;
use Modules\Shipping\Listeners\CreateDefaultShippingMethods;
use Modules\Shipping\Models\ShippingMethod;

class ShippingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'shipping';
    }

    public function boot(): void
    {
        parent::boot();

        // Preložiteľný obsah (hromadný preklad).
        $this->app->make(TranslatableModels::class)->register(ShippingMethod::class, fn () => __('shipping::admin.plural'));

        Event::listen(EshopInstalled::class, CreateDefaultShippingMethods::class);
    }
}
