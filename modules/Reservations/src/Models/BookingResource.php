<?php

namespace Modules\Reservations\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Zamestnanec alebo miestnosť. Rozvrh: {deň 1–7: [[„09:00“, „12:00“], [„13:00“, „17:00“]]}.
 *
 * @property int $id
 * @property string $name
 * @property string $type staff, room
 * @property array<string, list<array{0: string, 1: string}>>|null $schedule
 * @property bool $is_active
 */
class BookingResource extends Model
{
    protected $guarded = [];

    protected $attributes = ['type' => 'staff', 'is_active' => true, 'position' => 0];

    protected function casts(): array
    {
        return ['schedule' => 'array', 'is_active' => 'boolean'];
    }

    /**
     * @return BelongsToMany<BookingService, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(BookingService::class, 'booking_resource_service');
    }

    /**
     * Pracovné úseky v daný deň v týždni (1 = pondelok).
     *
     * @return list<array{0: string, 1: string}>
     */
    public function hoursFor(int $isoDay): array
    {
        return array_values(array_filter((array) ($this->schedule[(string) $isoDay] ?? []), fn ($range) => is_array($range) && count($range) === 2 && $range[0] < $range[1]));
    }
}
