<?php

namespace Modules\Shipping;

use Illuminate\Support\Facades\Log;
use Modules\Shipping\Carriers\CarrierRegistry;
use Modules\Shipping\Models\ShippingMethod;
use Throwable;

/**
 * Stiahne zoznamy výdajných miest kuriérov, ktoré sa v obchode používajú.
 */
class SyncPickupPoints
{
    /**
     * @return array<string, int|string> kuriér => počet miest alebo chybová správa
     */
    public function handle(): array
    {
        $results = [];
        $used = ShippingMethod::query()->where('type', ShippingType::PickupPoint)->whereNotNull('carrier')->distinct()->pluck('carrier');

        foreach ($used as $key) {
            $carrier = app(CarrierRegistry::class)->find($key);

            if ($carrier === null || ! $carrier->isConfigured()) {
                continue;
            }

            try {
                $results[$key] = $carrier->syncPickupPoints();
            } catch (Throwable $e) {
                // Pri výpadku ostane posledný stiahnutý zoznam.
                Log::warning('Pickup points sync failed.', ['module' => $key, 'message' => $e->getMessage()]);
                $results[$key] = $e->getMessage();
            }
        }

        return $results;
    }
}
