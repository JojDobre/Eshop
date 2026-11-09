<?php

namespace Modules\Reservations;

use App\Core\Installer\Installation;
use App\Core\Mail\EmailTemplateDefinition;
use App\Core\Mail\EmailTemplates;
use App\Core\Modules\ModuleServiceProvider;
use App\Core\Privacy\DataRetention;
use App\Core\Privacy\RetentionPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;
use Modules\Catalog\Models\Product;
use Modules\Reservations\Models\Booking;
use Modules\Transfer\Exports\ExportDefinition;
use Modules\Transfer\Exports\Exports;

class ReservationsServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'reservations';
    }

    public function boot(): void
    {
        parent::boot();

        // GDPR: kontaktné údaje pri starých rezerváciách.
        $this->callAfterResolving(DataRetention::class, fn (DataRetention $retention) => $retention->register(new RetentionPolicy(
            'bookings', fn () => __('admin.privacy.policies.bookings'), fn () => __('admin.privacy.policies.bookings_help'), 730, true,
            function (Carbon $before): int {
                $count = 0;
                Booking::query()->where('ends_at', '<', $before)->where('email', 'not like', '%@anonymized.invalid')
                    ->eachById(function (Booking $booking) use (&$count): void {
                        $booking->forceFill(['customer_name' => '—', 'email' => 'booking-'.$booking->id.'@anonymized.invalid', 'phone' => null, 'note' => null])->save();
                        $count++;
                    });

                return $count;
            },
            minDays: 30,
        )));

        // Rezervovaný jedinečný kus sa nedá kúpiť.
        Product::addAvailabilityCheck('reservations', fn (Product $product) => ! $product->isUnique() || ! Bookings::isProductReserved($product->id));

        View::composer('theme::templates.product', function (\Illuminate\View\View $view): void {
            $product = $view->getData()['product'] ?? null;

            if ($product instanceof Product && $product->isUnique()) {
                $settings = app(ReservationSettings::class);
                $view->with('productReservation', [
                    'reserved' => Bookings::isProductReserved($product->id),
                    'days' => $settings->product_hold_days,
                    'deposit' => $settings->product_deposit > 0 ? format_price($settings->product_deposit) : 0,
                    'action' => route('reservations.product', $product),
                ]);
            }
        });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->call(fn () => app(Bookings::class)->sendReminders())
                ->name('reservations:reminders')
                ->everyFifteenMinutes()
                ->when(fn () => app(Installation::class)->isInstalled());
        });

        // Export rezervácií v module Import a export (ak je zapnutý).
        if (class_exists(Exports::class)) {
            $this->callAfterResolving(Exports::class, function (Exports $exports): void {
                $c = fn (string $key) => __('reservations::admin.'.$key);
                $exports->register(new ExportDefinition('reservations', fn () => __('reservations::admin.plural'), fn () => [
                    'number' => [$c('number'), fn (Booking $b) => $b->number],
                    'title' => [$c('service'), fn (Booking $b) => $b->title()],
                    'resource' => [$c('resource'), fn (Booking $b) => $b->resource?->name],
                    'starts_at' => [$c('starts_at'), fn (Booking $b) => $b->starts_at->format('Y-m-d H:i')],
                    'ends_at' => [$c('ends_at'), fn (Booking $b) => $b->ends_at->format('Y-m-d H:i')],
                    'status' => [$c('status'), fn (Booking $b) => __('reservations::admin.statuses.'.$b->status)],
                    'customer' => [$c('customer'), fn (Booking $b) => $b->customer_name],
                    'email' => [$c('email'), fn (Booking $b) => $b->email],
                    'phone' => [$c('phone'), fn (Booking $b) => $b->phone],
                    'amount_due' => [$c('amount_due'), fn (Booking $b) => Exports::money($b->amount_due, $b->currency)],
                ], fn (?string $from, ?string $to) => Booking::query()->with(['service', 'resource', 'product'])
                    ->when($from, fn ($q) => $q->whereDate('starts_at', '>=', $from))->when($to, fn ($q) => $q->whereDate('starts_at', '<=', $to))->orderBy('id'), hasPeriod: true));
            });
        }

        $variables = ['customer_name', 'booking_number', 'booking_title', 'booking_date', 'booking_resource', 'amount_due', 'booking_url', 'shop_name'];
        $sample = ['customer_name' => 'Jana Nováková', 'booking_number' => 'B'.now()->format('Y').'00001', 'booking_title' => 'Strihanie', 'booking_date' => now()->addDay()->format('j. n. Y, H:i'),
            'booking_resource' => 'Eva', 'amount_due' => '', 'booking_url' => url('/rezervacia')];
        $templates = $this->app->make(EmailTemplates::class);

        foreach (['confirmation', 'reminder', 'cancelled'] as $key) {
            $templates->register(new EmailTemplateDefinition('reservations.'.$key, 'reservations::mail_templates.'.$key, $variables, canBeDisabled: $key !== 'confirmation', sample: $sample));
        }

        $templates->register(new EmailTemplateDefinition('reservations.notification', 'reservations::mail_templates.notification', [...$variables, 'booking_status'], canBeDisabled: true, sample: [...$sample, 'booking_status' => 'Potvrdená']));
    }
}
