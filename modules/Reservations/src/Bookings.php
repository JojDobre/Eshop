<?php

namespace Modules\Reservations;

use App\Core\Content\ContentLocale;
use App\Core\Mail\TemplateMail;
use App\Core\Support\Currencies;
use App\Settings\GeneralSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Catalog\Models\Product;
use Modules\Reservations\Models\Booking;
use Modules\Reservations\Models\BookingService;
use RuntimeException;
use Throwable;

/**
 * Vytvorenie, zrušenie a pripomienky rezervácií.
 */
class Bookings
{
    public function __construct(private readonly Availability $availability, private readonly ReservationSettings $settings) {}

    /**
     * Rezervuje termín služby. Termín sa overí znova so zámkom, aby ho nezískali dvaja zákazníci naraz.
     *
     * @param  array{customer_name: string, email: string, phone?: ?string, note?: ?string}  $customer
     *
     * @throws RuntimeException ak už termín nie je voľný
     */
    public function bookService(BookingService $service, Carbon $start, array $customer, ?int $resourceId = null, bool $notify = true): Booking
    {
        $booking = DB::transaction(function () use ($service, $start, $customer, $resourceId): Booking {
            // Zámok na službe: rezervácie tej istej služby sa spracujú jedna po druhej.
            BookingService::query()->whereKey($service->id)->lockForUpdate()->first();
            $free = $this->availability->slots($service, $start)[$start->format('H:i')] ?? [];

            if ($resourceId !== null) {
                $free = in_array($resourceId, $free, true) ? [$resourceId] : [];
            }

            if ($free === []) {
                throw new RuntimeException(__('reservations::shop.slot_taken'));
            }

            $due = $service->amountDue();

            return Booking::query()->create([
                ...$this->base($customer),
                'booking_service_id' => $service->id,
                'booking_resource_id' => $free[0],
                'starts_at' => $start,
                'ends_at' => $start->copy()->addMinutes($service->duration_minutes),
                'status' => $due > 0 ? Booking::PENDING : Booking::CONFIRMED,
                'payment_mode' => $service->payment_mode,
                'amount_due' => $due,
                'currency' => $service->currency,
            ]);
        });

        if ($notify) {
            $this->notify($booking, 'reservations.confirmation');
        }

        return $booking;
    }

    /**
     * Rezervácia jedinečného kusu (napr. zvieraťa) na nastavený počet dní – počas nej sa nedá kúpiť.
     *
     * @param  array{customer_name: string, email: string, phone?: ?string, note?: ?string}  $customer
     */
    public function bookProduct(Product $product, array $customer): Booking
    {
        $booking = DB::transaction(function () use ($product, $customer): Booking {
            Product::query()->whereKey($product->id)->lockForUpdate()->first();

            if (self::isProductReserved($product->id)) {
                throw new RuntimeException(__('reservations::shop.product_taken'));
            }

            $deposit = $this->settings->product_deposit;

            return Booking::query()->create([
                ...$this->base($customer),
                'product_id' => $product->id,
                'starts_at' => now(),
                'ends_at' => now()->addDays(max(1, $this->settings->product_hold_days)),
                'status' => $deposit > 0 ? Booking::PENDING : Booking::CONFIRMED,
                'payment_mode' => $deposit > 0 ? 'deposit' : 'on_site',
                'amount_due' => $deposit,
                'currency' => app(Currencies::class)->default(),
            ]);
        });

        $this->notify($booking, 'reservations.confirmation');

        return $booking;
    }

    public static function isProductReserved(int $productId): bool
    {
        return Booking::query()->active()->where('product_id', $productId)->where('status', '!=', Booking::COMPLETED)->where('ends_at', '>', now())->exists();
    }

    /**
     * @param  array<string, mixed>  $customer
     * @return array<string, mixed>
     */
    private function base(array $customer): array
    {
        return [
            'number' => Booking::nextNumber(),
            'token' => Str::random(40),
            'customer_name' => trim((string) $customer['customer_name']),
            'email' => Str::lower(trim((string) $customer['email'])),
            'phone' => $customer['phone'] ?? null,
            'note' => $customer['note'] ?? null,
            'locale' => app(ContentLocale::class)->current(),
        ];
    }

    public function cancel(Booking $booking, bool $notify = true): void
    {
        $booking->update(['status' => Booking::CANCELLED, 'cancelled_at' => now()]);

        if ($notify) {
            $this->notify($booking, 'reservations.cancelled');
        }
    }

    /** Prijatá platba alebo potvrdenie obchodom: rezervácia je potvrdená. */
    public function confirm(Booking $booking, bool $paid, bool $notify = true): void
    {
        $booking->update(['status' => Booking::CONFIRMED, 'is_paid' => $paid || $booking->is_paid]);

        if ($notify) {
            $this->notify($booking, 'reservations.confirmation', admin: false);
        }
    }

    /** Termín prebehol (alebo si zákazník kus prevzal). */
    public function complete(Booking $booking): void
    {
        $booking->update(['status' => Booking::COMPLETED]);
    }

    /** Pripomienky pred začiatkom (webový cron). */
    public function sendReminders(): int
    {
        if ($this->settings->reminder_hours <= 0) {
            return 0;
        }

        $sent = 0;
        $bookings = Booking::query()->where('status', Booking::CONFIRMED)->whereNull('product_id')->whereNull('reminded_at')
            ->where('starts_at', '>', now())->where('starts_at', '<=', now()->addHours($this->settings->reminder_hours))->get();

        foreach ($bookings as $booking) {
            $booking->forceFill(['reminded_at' => now()])->save();
            $this->notify($booking, 'reservations.reminder', admin: false);
            $sent++;
        }

        return $sent;
    }

    /**
     * @return array<string, string>
     */
    public static function variables(Booking $booking): array
    {
        $format = $booking->locale === 'en' ? 'j M Y, H:i' : 'j. n. Y, H:i';

        return [
            'customer_name' => $booking->customer_name,
            'booking_number' => $booking->number,
            'booking_title' => $booking->title(),
            'booking_date' => $booking->product_id ? $booking->ends_at->format($format) : $booking->starts_at->format($format),
            'booking_resource' => (string) $booking->resource?->name,
            'amount_due' => $booking->amount_due > 0 && ! $booking->is_paid ? format_price($booking->amount_due, $booking->currency) : '',
            'booking_url' => $booking->url(),
        ];
    }

    private function notify(Booking $booking, string $template, bool $admin = true): void
    {
        try {
            Mail::to($booking->email, $booking->customer_name)->send(new TemplateMail($template, self::variables($booking), $booking->locale, ['booking_url']));

            if ($admin && filled($email = app(GeneralSettings::class)->contact_email)) {
                Mail::to($email)->send(new TemplateMail('reservations.notification', [...self::variables($booking), 'booking_status' => __('reservations::admin.statuses.'.$booking->status)], null, ['booking_url']));
            }
        } catch (Throwable $e) {
            report($e);
        }
    }
}
