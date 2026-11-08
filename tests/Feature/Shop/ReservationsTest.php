<?php

namespace Tests\Feature\Shop;

use App\Core\Mail\TemplateMail;
use App\Settings\CatalogSettings;
use App\Settings\GeneralSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Modules\Catalog\Models\ProductType;
use Modules\Reservations\Availability;
use Modules\Reservations\Bookings;
use Modules\Reservations\Filament\Pages\ManageReservationSettings;
use Modules\Reservations\Filament\Pages\ReservationCalendar;
use Modules\Reservations\Filament\Resources\BookingResources\Pages\ManageBookingResources;
use Modules\Reservations\Filament\Resources\Bookings\Pages\ListBookings;
use Modules\Reservations\Models\Booking;
use Modules\Reservations\Models\BookingClosure;
use Modules\Reservations\Models\BookingResource;
use Modules\Reservations\Models\BookingService;
use Modules\Reservations\ReservationSettings;
use RuntimeException;

class ReservationsTest extends ShopTestCase
{
    private BookingService $service;

    private BookingResource $eva;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        // Pondelok 10. mája o 8:00 – termíny sa počítajú od zajtra.
        $this->travelTo(Carbon::parse('2027-05-10 08:00'));
        app(ReservationSettings::class)->fill(['lead_hours' => 2, 'cancel_hours' => 24, 'reminder_hours' => 24])->save();

        $this->eva = BookingResource::query()->create(['name' => 'Eva', 'schedule' => [
            '2' => [['09:00', '12:00'], ['13:00', '15:00']],
            '3' => [['09:00', '11:00']],
        ]]);
        $this->service = BookingService::query()->create(['name' => 'Strihanie', 'duration_minutes' => 60, 'price' => 2000]);
        $this->service->resources()->attach($this->eva);
    }

    private function customer(array $extra = []): array
    {
        return ['customer_name' => 'Jana Nováková', 'email' => 'Jana@Example.com', ...$extra];
    }

    public function test_slots_respect_schedule_breaks_buffer_and_closures(): void
    {
        $availability = app(Availability::class);

        // Pondelok nepracuje; utorok má obedňajšiu prestávku.
        $this->assertSame([], $availability->slots($this->service, Carbon::parse('2027-05-10')));
        $this->assertSame(['09:00', '10:00', '11:00', '13:00', '14:00'], array_keys($availability->slots($this->service, Carbon::parse('2027-05-11'))));
        $this->assertSame(['2027-05-11', '2027-05-12'], array_slice($availability->availableDays($this->service, 7), 0, 2));

        // Prestávka po termíne posunie ďalší voľný začiatok.
        $this->service->update(['buffer_minutes' => 30]);
        app(Bookings::class)->bookService($this->service, Carbon::parse('2027-05-11 09:00'), $this->customer());
        $this->assertSame(['11:00', '13:00', '14:00'], array_keys($availability->slots($this->service->fresh(), Carbon::parse('2027-05-11'))));

        // Dovolenka zamestnanca aj sviatok pre celú prevádzku.
        BookingClosure::query()->create(['booking_resource_id' => $this->eva->id, 'from_date' => '2027-05-12', 'to_date' => '2027-05-12']);
        $this->assertSame([], $availability->slots($this->service, Carbon::parse('2027-05-12')));
        BookingClosure::query()->create(['from_date' => '2027-05-18', 'to_date' => '2027-05-18']);
        $this->assertSame([], $availability->slots($this->service, Carbon::parse('2027-05-18')));
    }

    public function test_slot_cannot_be_booked_twice_but_group_capacity_works(): void
    {
        $bookings = app(Bookings::class);
        $start = Carbon::parse('2027-05-11 10:00');
        $bookings->bookService($this->service, $start, $this->customer());

        try {
            $bookings->bookService($this->service, $start, $this->customer(['customer_name' => 'Peter']));
            $this->fail('Termín sa nesmie dať rezervovať dvakrát.');
        } catch (RuntimeException $e) {
            $this->assertSame(__('reservations::shop.slot_taken'), $e->getMessage());
        }

        $yoga = BookingService::query()->create(['name' => 'Joga', 'duration_minutes' => 60, 'capacity' => 2]);
        $yoga->resources()->attach($this->eva);
        $bookings->bookService($yoga, Carbon::parse('2027-05-11 13:00'), $this->customer());
        $bookings->bookService($yoga, Carbon::parse('2027-05-11 13:00'), $this->customer());
        $this->expectException(RuntimeException::class);
        $bookings->bookService($yoga, Carbon::parse('2027-05-11 13:00'), $this->customer());
    }

    public function test_customer_books_pays_and_cancels_online(): void
    {
        app(GeneralSettings::class)->fill(['contact_email' => 'obchod@example.com'])->save();
        $this->service->update(['payment_mode' => 'deposit', 'deposit' => 500]);

        $this->get(route('reservations.index'))->assertOk()->assertSee('Strihanie');
        $this->get(route('reservations.service', ['service' => $this->service, 'date' => '2027-05-11']))->assertOk()->assertSee('13:00');
        $this->post(route('reservations.book', $this->service), ['date' => '2027-05-11', 'time' => '13:00', ...$this->customer()])->assertSessionHasErrors('terms');

        $response = $this->post(route('reservations.book', $this->service), ['date' => '2027-05-11', 'time' => '13:00', 'terms' => '1', ...$this->customer()]);
        $booking = Booking::query()->sole();
        $response->assertRedirect($booking->url());

        $this->assertSame(Booking::PENDING, $booking->status);
        $this->assertSame(500, $booking->amount_due);
        $this->assertSame('jana@example.com', $booking->email);
        $this->assertSame($this->eva->id, $booking->booking_resource_id);
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'reservations.confirmation' && $mail->hasTo('jana@example.com') && $mail->variables['booking_date'] === '11. 5. 2027, 13:00');
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'reservations.notification' && $mail->hasTo('obchod@example.com'));

        $this->get($booking->url())->assertOk()->assertSee($booking->number)->assertSee('Eva');
        $ics = $this->get(route('reservations.ics', $booking->token))->assertOk()->assertHeader('Content-Type', 'text/calendar; charset=UTF-8')->getContent();
        $this->assertStringContainsString('DTSTART:'.Carbon::parse('2027-05-11 13:00')->utc()->format('Ymd\THis\Z'), $ics);
        $this->assertStringContainsString('STATUS:TENTATIVE', $ics);

        // Menej ako 24 hodín pred začiatkom sa už online zrušiť nedá.
        $this->travelTo(Carbon::parse('2027-05-10 14:00'));
        $this->post(route('reservations.cancel', $booking->token))->assertSessionHas('error');
        $this->travelTo(Carbon::parse('2027-05-10 12:00'));
        $this->post(route('reservations.cancel', $booking->token))->assertSessionHas('success');
        $this->assertSame(Booking::CANCELLED, $booking->fresh()->status);
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'reservations.cancelled');
    }

    public function test_reminders_are_sent_once(): void
    {
        $booking = app(Bookings::class)->bookService($this->service, Carbon::parse('2027-05-12 09:00'), $this->customer(), notify: false);

        $this->assertSame(0, app(Bookings::class)->sendReminders());
        $this->travelTo(Carbon::parse('2027-05-11 10:00'));
        $this->assertSame(1, app(Bookings::class)->sendReminders());
        $this->assertSame(0, app(Bookings::class)->sendReminders());
        $this->assertNotNull($booking->fresh()->reminded_at);
        Mail::assertSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->templateKey === 'reservations.reminder');
    }

    public function test_unique_product_reservation_blocks_purchase(): void
    {
        $catalog = app(CatalogSettings::class);
        $catalog->feature_product_types = true;
        $catalog->save();
        $animals = ProductType::query()->get()->first(fn ($type) => $type->getTranslation('name', 'sk') === 'Zvieratá');
        $cat = $this->product('Mačka Micka', '150.00', 1, attributes: ['product_type_id' => $animals->id]);
        app(ReservationSettings::class)->fill(['product_hold_days' => 3])->save();

        $this->get($cat->url())->assertSee(__('reservations::shop.reserve_product', ['days' => 3]));
        $this->post(route('reservations.product', $cat), $this->customer())->assertRedirect();

        $booking = Booking::query()->sole();
        $this->assertSame($cat->id, $booking->product_id);
        $this->assertTrue($booking->ends_at->eq(now()->addDays(3)));
        $this->assertFalse($cat->fresh()->isAvailable());
        $this->get($cat->url())->assertSee(__('reservations::shop.product_reserved'));

        // Iný zákazník si ho nerezervuje ani nekúpi.
        $this->post(route('reservations.product', $cat), $this->customer(['email' => 'iny@example.com']))->assertSessionHas('error');
        $this->assertSame(1, Booking::query()->count());

        // Po uplynutí rezervácie je opäť na predaj.
        $this->travel(4)->days();
        $this->assertTrue($cat->fresh()->isAvailable());
    }

    public function test_admin_pages_manual_booking_status_and_ical_feed(): void
    {
        $this->actingAs($this->superAdmin());
        foreach (['/admin/reservations', '/admin/reservations/calendar', '/admin/reservation-services', '/admin/reservation-resources', '/admin/reservation-closures', '/admin/settings/reservations'] as $url) {
            $this->get($url)->assertOk();
        }

        Livewire::test(ManageBookingResources::class)
            ->callAction('create', data: ['name' => 'Miestnosť A', 'type' => 'room', 'schedule' => ['1' => [['0' => '10:00', '1' => '12:00']]]])
            ->assertHasNoActionErrors();
        $this->assertSame([['10:00', '12:00']], BookingResource::query()->where('name', 'Miestnosť A')->sole()->hoursFor(1));

        Livewire::test(ListBookings::class)
            ->callAction('book', data: ['service' => $this->service->id, 'date' => '2027-05-11', 'time' => '09:00', 'customer_name' => 'Telefonická', 'email' => 'tel@example.com', 'notify' => false])
            ->assertHasNoActionErrors();
        $booking = Booking::query()->sole();
        $this->assertSame('Telefonická', $booking->customer_name);
        Mail::assertNotSent(TemplateMail::class, fn (TemplateMail $mail) => $mail->hasTo('tel@example.com'));

        Livewire::test(ListBookings::class)->assertCanSeeTableRecords([$booking])
            ->callTableAction('complete', $booking);
        $this->assertSame(Booking::COMPLETED, $booking->fresh()->status);

        Livewire::test(ReservationCalendar::class)->set('date', '2027-05-11')->assertSee('Telefonická')
            ->set('mode', 'month')->call('move', 1)->assertDontSee('Telefonická');
        Livewire::test(ManageReservationSettings::class)->assertSee(route('reservations.feed', app(ReservationSettings::class)->ical_token));

        $token = app(ReservationSettings::class)->ical_token;
        $this->get(route('reservations.feed', $token))->assertOk()->assertSee('Telefonická');
        $this->get(route('reservations.feed', str_repeat('x', 40)))->assertNotFound();
    }
}
