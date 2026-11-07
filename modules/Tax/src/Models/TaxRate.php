<?php

namespace Modules\Tax\Models;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $rate Sadzba v percentách, napr. „23.00".
 * @property bool $is_default
 * @property int $position
 */
class TaxRate extends Model
{
    use HasContentTranslations;

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'country_rates' => 'array',
            'rate' => 'decimal:2',
            'is_default' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Predvolená môže byť iba jedna sadzba.
        static::saved(function (TaxRate $rate): void {
            if ($rate->is_default) {
                static::query()->whereKeyNot($rate->id)->where('is_default', true)->update(['is_default' => false]);
            }
        });
    }

    public static function default(): ?self
    {
        return static::query()->where('is_default', true)->first() ?? static::query()->orderByDesc('rate')->first();
    }

    public function percent(): float
    {
        return (float) $this->rate;
    }

    public function label(): string
    {
        return $this->name.' ('.rtrim(rtrim(number_format($this->percent(), 2, ',', ''), '0'), ',').' %)';
    }

    /**
     * Sadzba v krajine (null = sadzba pre krajinu nie je zadaná).
     */
    public function rateFor(string $country, string $homeCountry): ?float
    {
        if ($country === $homeCountry) {
            return (float) $this->rate;
        }

        $rate = $this->country_rates[$country] ?? null;

        return is_numeric($rate) ? (float) $rate : null;
    }
}
