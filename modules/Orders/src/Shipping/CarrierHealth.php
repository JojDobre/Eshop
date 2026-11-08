<?php

namespace Modules\Orders\Shipping;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Shipping\Carriers\CarrierException;
use Throwable;

/**
 * Sledovanie výpadkov API kuriérov (PLAN.md, kapitola 2.6): chyby sa zapíšu do logu s menom kuriéra,
 * pri opakovaných výpadkoch sa kuriér dočasne označí ako nedostupný (automatické sledovanie ho preskočí)
 * a po čase sa skúsi znova.
 */
class CarrierHealth
{
    /** Počet výpadkov v rade, po ktorom sa kuriér dočasne neskúša. */
    public const FAILURE_LIMIT = 5;

    /** Ako dlho sa kuriér po opakovaných výpadkoch neskúša (minúty). */
    public const PAUSE_MINUTES = 30;

    public function isAvailable(string $carrier): bool
    {
        return ! Cache::has($this->pauseKey($carrier));
    }

    public function success(string $carrier): void
    {
        Cache::forget($this->failuresKey($carrier));
    }

    public function failure(string $carrier, Throwable $e): void
    {
        Log::warning('Carrier request failed.', ['module' => $carrier, 'type' => $e instanceof CarrierException ? $e->type : 'code', 'message' => $e->getMessage()]);

        if (! $e instanceof CarrierException || ! $e->isTemporary()) {
            return;
        }

        $failures = (int) Cache::get($this->failuresKey($carrier), 0) + 1;
        Cache::put($this->failuresKey($carrier), $failures, now()->addHour());

        if ($failures >= self::FAILURE_LIMIT) {
            Cache::put($this->pauseKey($carrier), true, now()->addMinutes(self::PAUSE_MINUTES));
            Cache::forget($this->failuresKey($carrier));
        }
    }

    private function failuresKey(string $carrier): string
    {
        return 'eshop.carrier.failures.'.$carrier;
    }

    private function pauseKey(string $carrier): string
    {
        return 'eshop.carrier.paused.'.$carrier;
    }
}
