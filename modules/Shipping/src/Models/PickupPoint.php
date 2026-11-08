<?php

namespace Modules\Shipping\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Výdajné miesto kuriéra uložené v obchode (pre kuriérov bez vlastného výberového okna).
 *
 * @property int $id
 * @property string $carrier
 * @property string $code
 * @property string $name
 * @property string|null $street
 * @property string|null $city
 * @property string|null $zip
 * @property string $country
 * @property string|null $type
 * @property string|null $opening_hours
 * @property bool $is_active
 */
class PickupPoint extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'latitude' => 'float', 'longitude' => 'float', 'synced_at' => 'datetime'];
    }

    /**
     * Vyhľadanie podľa mesta, PSČ, ulice alebo názvu.
     *
     * @param  Builder<PickupPoint>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        foreach (preg_split('/\s+/', trim($term)) ?: [] as $word) {
            if ($word === '') {
                continue;
            }

            $like = '%'.addcslashes($word, '%_\\').'%';
            $query->where(fn ($inner) => $inner->where('city', 'like', $like)->orWhere('zip', 'like', str_replace(' ', '', $like))
                ->orWhere('street', 'like', $like)->orWhere('name', 'like', $like));
        }
    }

    public function address(): string
    {
        return trim(implode(', ', array_filter([$this->street, trim($this->zip.' '.$this->city)])));
    }

    /**
     * Údaje uložené v objednávke.
     *
     * @return array{code: string, name: string, street: string|null, city: string|null, zip: string|null, country: string}
     */
    public function toOrderData(): array
    {
        return ['code' => $this->code, 'name' => $this->name, 'street' => $this->street, 'city' => $this->city, 'zip' => $this->zip, 'country' => $this->country];
    }

    /**
     * Uloží alebo aktualizuje výdajné miesta kuriéra a ostatné označí ako neaktívne.
     *
     * @param  iterable<array<string, mixed>>  $points
     */
    public static function syncCarrier(string $carrier, iterable $points): int
    {
        $now = now();
        $count = 0;
        $batch = [];

        foreach ($points as $point) {
            // Všetky riadky musia mať rovnaké stĺpce (hromadný upsert).
            $batch[] = [
                'carrier' => $carrier,
                'code' => (string) $point['code'],
                'name' => mb_substr((string) $point['name'], 0, 255),
                'street' => $point['street'] ?? null,
                'city' => $point['city'] ?? null,
                'zip' => $point['zip'] ?? null,
                'country' => strtoupper((string) $point['country']),
                'type' => $point['type'] ?? null,
                'latitude' => $point['latitude'] ?? null,
                'longitude' => $point['longitude'] ?? null,
                'opening_hours' => $point['opening_hours'] ?? null,
                'is_active' => $point['is_active'] ?? true,
                'synced_at' => $now,
            ];

            if (count($batch) === 500) {
                $count += self::flush($batch);
                $batch = [];
            }
        }

        $count += self::flush($batch);
        self::query()->where('carrier', $carrier)->where(fn ($query) => $query->whereNull('synced_at')->orWhere('synced_at', '<', $now))->update(['is_active' => false]);

        return $count;
    }

    /**
     * @param  list<array<string, mixed>>  $batch
     */
    private static function flush(array $batch): int
    {
        if ($batch === []) {
            return 0;
        }

        self::query()->upsert($batch, ['carrier', 'code'], ['name', 'street', 'city', 'zip', 'country', 'type', 'latitude', 'longitude', 'opening_hours', 'is_active', 'synced_at']);

        return count($batch);
    }
}
