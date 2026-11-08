<?php

namespace Modules\Orders\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Shipping\Models\PickupPoint;
use Modules\Shipping\Models\ShippingMethod;

/**
 * Vyhľadanie výdajných miest v pokladni (kuriéri bez vlastného výberového okna).
 */
class PickupPointController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $method = ShippingMethod::query()->active()->find($request->integer('method'));
        $term = trim((string) $request->query('q', ''));

        if ($method === null || $method->carrier === null || mb_strlen($term) < 2) {
            return response()->json(['points' => []]);
        }

        $country = $method->carrier_options['country'] ?? null;

        $points = PickupPoint::query()
            ->where('carrier', $method->carrier)
            ->where('is_active', true)
            ->when($country, fn ($query) => $query->where('country', strtoupper((string) $country)))
            ->search($term)
            ->orderBy('city')->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn (PickupPoint $point) => [
                'code' => $point->code,
                'name' => $point->name,
                'address' => $point->address(),
                'type' => $point->type,
                'opening_hours' => $point->opening_hours,
            ]);

        return response()->json(['points' => $points]);
    }
}
