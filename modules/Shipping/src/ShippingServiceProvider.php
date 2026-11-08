<?php

namespace Modules\Shipping;

use App\Core\Content\TranslatableModels;
use App\Core\Events\EshopInstalled;
use App\Core\Installer\Installation;
use App\Core\Modules\ModuleServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Event;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Carriers\ManualCarrier;
use Modules\Shipping\Listeners\CreateDefaultShippingMethods;
use Modules\Shipping\Models\ShippingMethod;

class ShippingServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'shipping';
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton(CarrierRegistry::class, function (): CarrierRegistry {
            $registry = new CarrierRegistry;
            $registry->register(new ManualCarrier);

            return $registry;
        });
    }

    public function boot(): void
    {
        parent::boot();

        // Aktualizácia zoznamov výdajných miest kuriérov raz denne (webový cron).
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->call(fn () => app(SyncPickupPoints::class)->handle())
                ->name('shipping:sync-pickup-points')
                ->dailyAt('04:20')
                ->withoutOverlapping(30)
                ->when(fn () => app(Installation::class)->isInstalled());
        });

        // Preložiteľný obsah (hromadný preklad).
        $this->app->make(TranslatableModels::class)->register(ShippingMethod::class, fn () => __('shipping::admin.plural'));

        Event::listen(EshopInstalled::class, CreateDefaultShippingMethods::class);
    }
}
