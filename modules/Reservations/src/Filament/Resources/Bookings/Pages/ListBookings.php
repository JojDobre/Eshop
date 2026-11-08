<?php

namespace Modules\Reservations\Filament\Resources\Bookings\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Modules\Reservations\Availability;
use Modules\Reservations\Bookings;
use Modules\Reservations\Filament\Pages\ReservationCalendar;
use Modules\Reservations\Filament\Resources\Bookings\BookingResource;
use Modules\Reservations\Models\BookingResource as Resource;
use Modules\Reservations\Models\BookingService;
use RuntimeException;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        $t = fn (string $key) => __('reservations::admin.'.$key);

        return [
            Action::make('calendar')->label($t('calendar'))->icon(Heroicon::OutlinedCalendar)->color('gray')->url(ReservationCalendar::getUrl()),
            // Ručná rezervácia (telefonická, osobná) – termín sa vyberá z voľných.
            Action::make('book')->label($t('book'))->icon(Heroicon::OutlinedPlus)
                ->schema([
                    Select::make('service')->label($t('service'))->required()->live()
                        ->options(fn () => BookingService::query()->active()->get()->mapWithKeys(fn (BookingService $s) => [$s->id => $s->name])->all()),
                    DatePicker::make('date')->label($t('date'))->required()->native(false)->live()->minDate(today()),
                    Select::make('time')->label($t('time'))->required()->live()
                        ->options(fn ($get) => self::slotOptions($get('service'), $get('date'))),
                    Select::make('resource')->label($t('resource'))->placeholder($t('any_resource'))
                        ->options(fn ($get) => Resource::query()->whereKey(self::slots($get('service'), $get('date'))[$get('time')] ?? [])->pluck('name', 'id')->all()),
                    TextInput::make('customer_name')->label($t('customer'))->required()->maxLength(120),
                    TextInput::make('email')->label($t('email'))->required()->email(),
                    TextInput::make('phone')->label($t('phone'))->tel()->maxLength(40),
                    Textarea::make('note')->label($t('note'))->rows(2)->maxLength(1000),
                    Toggle::make('notify')->label($t('notify_customer'))->default(true),
                ])
                ->action(function (array $data, Action $action) use ($t): void {
                    try {
                        $booking = app(Bookings::class)->bookService(
                            BookingService::query()->findOrFail($data['service']),
                            Carbon::parse($data['date'].' '.$data['time']),
                            $data,
                            filled($data['resource'] ?? null) ? (int) $data['resource'] : null,
                            (bool) $data['notify'],
                        );
                    } catch (RuntimeException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                        $action->halt();

                        return;
                    }

                    Notification::make()->title($t('booked', ['number' => $booking->number]))->success()->send();
                }),
        ];
    }

    /**
     * @return array<string, list<int>>
     */
    private static function slots(mixed $serviceId, mixed $date): array
    {
        $service = filled($serviceId) ? BookingService::query()->find($serviceId) : null;

        return $service && filled($date) ? app(Availability::class)->slots($service, Carbon::parse($date)) : [];
    }

    /**
     * @return array<string, string>
     */
    private static function slotOptions(mixed $serviceId, mixed $date): array
    {
        $times = array_keys(self::slots($serviceId, $date));

        return array_combine($times, $times) ?: [];
    }
}
