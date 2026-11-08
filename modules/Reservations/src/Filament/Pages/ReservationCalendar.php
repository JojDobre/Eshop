<?php

namespace Modules\Reservations\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Url;
use Modules\Reservations\Filament\ReservationsNav;
use Modules\Reservations\Filament\Resources\Bookings\BookingResource;
use Modules\Reservations\Models\Booking;
use Modules\Reservations\Models\BookingResource as Resource;

/**
 * Kalendár rezervácií: deň, týždeň alebo mesiac, s filtrom zamestnanca či miestnosti.
 */
class ReservationCalendar extends Page
{
    use ReservationsNav;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?int $navigationSort = 50;

    protected static ?string $slug = 'reservations/calendar';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'reservations::admin.calendar';

    #[Url]
    public string $mode = 'week';

    #[Url]
    public string $date = '';

    #[Url]
    public ?int $resource = null;

    public function mount(): void
    {
        $this->mode = in_array($this->mode, ['day', 'week', 'month'], true) ? $this->mode : 'week';
        $this->date = $this->current()->toDateString();
    }

    public function getTitle(): string
    {
        return __('reservations::admin.calendar');
    }

    public function getBreadcrumbs(): array
    {
        return [BookingResource::getUrl() => BookingResource::getPluralModelLabel(), __('reservations::admin.calendar')];
    }

    public function move(int $direction): void
    {
        $date = $this->current();
        $this->date = (match ($this->mode) {
            'day' => $date->addDays($direction),
            'week' => $date->addWeeks($direction),
            default => $date->addMonthsNoOverflow($direction),
        })->toDateString();
    }

    public function today(): void
    {
        $this->date = today()->toDateString();
    }

    private function current(): Carbon
    {
        try {
            return filled($this->date) ? Carbon::parse($this->date)->startOfDay() : today();
        } catch (\Throwable) {
            return today();
        }
    }

    /**
     * Zobrazené dni (mesiac začína pondelkom týždňa, v ktorom je prvý deň).
     *
     * @return list<Carbon>
     */
    private function days(): array
    {
        $date = $this->current();
        [$from, $to] = match ($this->mode) {
            'day' => [$date, $date],
            'week' => [$date->copy()->startOfWeek(), $date->copy()->endOfWeek()->startOfDay()],
            default => [$date->copy()->startOfMonth()->startOfWeek(), $date->copy()->endOfMonth()->endOfWeek()->startOfDay()],
        };

        $days = [];
        for ($day = $from->copy(); $day->lte($to); $day->addDay()) {
            $days[] = $day->copy();
        }

        return $days;
    }

    protected function getViewData(): array
    {
        $days = $this->days();
        $bookings = Booking::query()->with(['service', 'resource', 'product'])
            ->where('status', '!=', Booking::CANCELLED)
            ->where('starts_at', '<', end($days)->copy()->endOfDay())
            ->where('ends_at', '>', $days[0])
            ->when($this->resource, fn ($q) => $q->where('booking_resource_id', $this->resource))
            ->orderBy('starts_at')->get();

        return [
            'days' => $days,
            'month' => $this->current()->month,
            'byDay' => collect($days)->mapWithKeys(fn (Carbon $day) => [$day->toDateString() => $bookings->filter(
                fn (Booking $b) => $b->starts_at->lt($day->copy()->endOfDay()) && $b->ends_at->gt($day)
            )->values()])->all(),
            'resources' => Resource::query()->orderBy('position')->pluck('name', 'id')->all(),
            'label' => $this->label(),
        ];
    }

    private function label(): string
    {
        $date = $this->current()->locale(app()->getLocale());

        return match ($this->mode) {
            'day' => $date->isoFormat('dddd D. M. YYYY'),
            'week' => $date->copy()->startOfWeek()->format('j. n.').' – '.$date->copy()->endOfWeek()->format('j. n. Y'),
            default => $date->isoFormat('MMMM YYYY'),
        };
    }
}
