<?php

namespace Modules\Reservations\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Modules\Catalog\Models\Product;
use Modules\Reservations\Availability;
use Modules\Reservations\Bookings;
use Modules\Reservations\ICalendar;
use Modules\Reservations\Models\Booking;
use Modules\Reservations\Models\BookingService;
use Modules\Reservations\ReservationSettings;
use RuntimeException;

class ReservationController extends Controller
{
    public function __construct(private readonly Availability $availability, private readonly Bookings $bookings) {}

    public function index(): View
    {
        return view('reservations::index', ['services' => BookingService::query()->active()->get()]);
    }

    /**
     * Výber dňa a voľného termínu (bez JavaScriptu: deň a čas v parametroch adresy).
     */
    public function service(Request $request, BookingService $service): View
    {
        abort_unless($service->is_active, 404);

        $days = $this->availability->availableDays($service, 21);
        $date = in_array($request->query('date'), $days, true) ? (string) $request->query('date') : ($days[0] ?? null);

        return view('reservations::service', [
            'service' => $service,
            'days' => $days,
            'date' => $date,
            'slots' => $date ? array_keys($this->availability->slots($service, Carbon::parse($date))) : [],
            'time' => $request->query('time'),
        ]);
    }

    public function book(Request $request, BookingService $service): RedirectResponse
    {
        abort_unless($service->is_active, 404);

        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'time' => ['required', 'date_format:H:i'],
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],
            'terms' => ['accepted'],
        ]);

        try {
            $booking = $this->bookings->bookService($service, Carbon::parse($data['date'].' '.$data['time']), $data);
        } catch (RuntimeException $e) {
            return redirect()->route('reservations.service', ['service' => $service, 'date' => $data['date']])->with('error', $e->getMessage());
        }

        return redirect()->to($booking->url())->with('success', __('reservations::shop.booked'));
    }

    public function bookProduct(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->is_visible && $product->isUnique(), 404);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $booking = $this->bookings->bookProduct($product, $data);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->to($booking->url())->with('success', __('reservations::shop.booked'));
    }

    public function show(string $token): View
    {
        return view('reservations::booking', ['booking' => Booking::query()->where('token', $token)->with(['service', 'resource', 'product'])->firstOrFail()]);
    }

    public function cancel(string $token): RedirectResponse
    {
        $booking = Booking::query()->where('token', $token)->firstOrFail();

        if (! $booking->canBeCancelled()) {
            return back()->with('error', __('reservations::shop.cannot_cancel', ['hours' => app(ReservationSettings::class)->cancel_hours]));
        }

        $this->bookings->cancel($booking);

        return back()->with('success', __('reservations::shop.cancelled'));
    }

    public function ics(string $token): Response
    {
        $booking = Booking::query()->where('token', $token)->firstOrFail();

        return response(ICalendar::render([$booking], $booking->title()), 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$booking->number.'.ics"',
        ]);
    }

    /** Kalendár všetkých rezervácií pre obchodníka (tajná adresa z nastavení). */
    public function feed(string $token): Response
    {
        abort_unless(hash_equals(app(ReservationSettings::class)->ical_token, $token), 404);

        $bookings = Booking::query()->with(['service', 'product'])->where('starts_at', '>=', now()->subMonths(3))->orderBy('starts_at')->get();

        return response(ICalendar::render($bookings, (string) config('app.name')), 200, ['Content-Type' => 'text/calendar; charset=UTF-8']);
    }
}
