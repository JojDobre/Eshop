<?php

namespace Modules\Reservations;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Modules\Reservations\Models\Booking;
use Modules\Reservations\Models\BookingClosure;
use Modules\Reservations\Models\BookingResource;
use Modules\Reservations\Models\BookingService;

/**
 * Voľné termíny služby: rozvrh zdrojov (zamestnanci, miestnosti) mínus sviatky, dovolenky a obsadené termíny,
 * s kapacitou služby a prestávkou po termíne.
 */
class Availability
{
    /** Krok, po ktorom sa ponúkajú začiatky termínov (minúty). */
    public const STEP = 15;

    public function __construct(private readonly ReservationSettings $settings) {}

    /**
     * Voľné začiatky v daný deň: [„HH:MM“ => [ID zdrojov, ktoré sú voľné]].
     *
     * @return array<string, list<int>>
     */
    public function slots(BookingService $service, Carbon $date): array
    {
        $date = $date->copy()->startOfDay();

        if ($date->lt(now()->startOfDay()) || $date->gt(now()->addDays($this->settings->horizon_days))) {
            return [];
        }

        $resources = $service->resources()->where('is_active', true)->get();
        $closures = BookingClosure::query()->whereDate('from_date', '<=', $date)->whereDate('to_date', '>=', $date)->get();

        if ($resources->isEmpty() || $closures->contains(fn (BookingClosure $closure) => $closure->booking_resource_id === null)) {
            return [];
        }

        $bookings = Booking::query()->with('service')->active()->whereNull('product_id')
            ->where('starts_at', '<', $date->copy()->endOfDay())->where('ends_at', '>', $date)->get();
        $step = max(self::STEP, min(60, $service->duration_minutes));
        $earliest = now()->addHours($this->settings->lead_hours);
        $slots = [];

        foreach ($resources as $resource) {
            if ($closures->contains(fn (BookingClosure $closure) => $closure->booking_resource_id === $resource->id)) {
                continue;
            }

            foreach ($resource->hoursFor($date->dayOfWeekIso) as [$open, $close]) {
                $start = $date->copy()->setTimeFromTimeString($open);
                $end = $date->copy()->setTimeFromTimeString($close);

                for ($slot = $start->copy(); $slot->copy()->addMinutes($service->duration_minutes)->lte($end); $slot->addMinutes($step)) {
                    if ($slot->lt($earliest)) {
                        continue;
                    }

                    if ($this->fits($service, $resource, $slot, $bookings)) {
                        $slots[$slot->format('H:i')][] = $resource->id;
                    }
                }
            }
        }

        ksort($slots);

        return $slots;
    }

    /**
     * Je zdroj v tomto čase voľný? Rovnaká služba sa smie prekrývať do kapacity (skupinové termíny).
     *
     * @param  Collection<int, Booking>  $bookings
     */
    private function fits(BookingService $service, BookingResource $resource, Carbon $start, Collection $bookings): bool
    {
        $end = $start->copy()->addMinutes($service->duration_minutes + $service->buffer_minutes);
        $overlapping = $bookings->filter(fn (Booking $booking) => $booking->booking_resource_id === $resource->id
            && $booking->starts_at->lt($end) && $booking->ends_at->copy()->addMinutes($booking->service?->buffer_minutes ?? 0)->gt($start));

        if ($overlapping->isEmpty()) {
            return true;
        }

        // Skupinový termín: rovnaká služba, rovnaký začiatok a voľné miesto.
        return $overlapping->every(fn (Booking $booking) => $booking->booking_service_id === $service->id && $booking->starts_at->eq($start))
            && $overlapping->count() < $service->capacity;
    }

    /**
     * Dni s aspoň jedným voľným termínom (na výber dátumu).
     *
     * @return list<string> Y-m-d
     */
    public function availableDays(BookingService $service, int $days = 14): array
    {
        $result = [];

        for ($i = 0; $i < min($days, $this->settings->horizon_days + 1); $i++) {
            $date = now()->startOfDay()->addDays($i);

            if ($this->slots($service, $date) !== []) {
                $result[] = $date->toDateString();
            }
        }

        return $result;
    }
}
