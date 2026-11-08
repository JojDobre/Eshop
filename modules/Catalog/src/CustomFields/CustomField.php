<?php

namespace Modules\Catalog\CustomFields;

use App\Core\Content\HasContentTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Definícia vlastného poľa (parametra).
 *
 * @property int $id
 * @property string $entity
 * @property string $key
 * @property string $name
 * @property string|null $help
 * @property CustomFieldType $type
 * @property string|null $unit
 * @property list<array{key: string, label: array<string, string>|string}>|null $options
 * @property bool $is_required
 * @property bool $is_filterable
 * @property bool $is_visible
 * @property int $position
 */
class CustomField extends Model
{
    use HasContentTranslations;

    public const PRODUCT = 'product';

    public const CATEGORY = 'category';

    public const MANUFACTURER = 'manufacturer';

    public const CUSTOMER = 'customer';

    /** Polia objednávky vypĺňa zákazník v pokladni (napr. „Text na tortu“). */
    public const ORDER = 'order';

    /** Objekty, pre ktoré sa dajú vytvoriť polia (ostatné okrem produktu iba so zapnutou funkciou). */
    public const ENTITIES = [self::PRODUCT, self::CATEGORY, self::MANUFACTURER, self::CUSTOMER, self::ORDER];

    protected $guarded = [];

    /** @var list<string> */
    public array $translatable = ['name', 'help', 'group'];

    protected $attributes = [
        'entity' => self::PRODUCT,
        'is_required' => false,
        'is_filterable' => false,
        'is_visible' => true,
        'is_translatable' => false,
        'position' => 0,
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomFieldType::class,
            'options' => 'array',
            'is_required' => 'boolean',
            'is_filterable' => 'boolean',
            'is_visible' => 'boolean',
            'is_translatable' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (CustomField $field): void {
            if (blank($field->key)) {
                $field->key = Str::slug((string) $field->getTranslation('name', $field->getLocale(), false), '_') ?: 'field';
            }

            // Kľúče možností sa vytvoria z názvov (pri zmene názvu sa kľúč nemení, hodnoty zostanú platné).
            $field->options = collect($field->options ?? [])
                ->filter(fn ($option) => filled($option['label'] ?? null))
                ->map(fn ($option) => [
                    'key' => filled($option['key'] ?? null) ? $option['key'] : Str::slug(is_array($option['label']) ? (string) reset($option['label']) : (string) $option['label']),
                    // Názov možnosti sa ukladá podľa jazyka (pripravené na viac jazykov).
                    'label' => is_array($option['label'])
                        ? $option['label']
                        : [...self::existingLabels($field, $option['key'] ?? null), $field->getLocale() => (string) $option['label']],
                ])
                ->values()
                ->all() ?: null;
        });
    }

    /**
     * Doterajšie preklady názvu možnosti (aby sa úpravou v jednom jazyku nestratili ostatné).
     *
     * @return array<string, string>
     */
    private static function existingLabels(CustomField $field, ?string $key): array
    {
        $original = $field->getOriginal('options');
        $original = is_string($original) ? json_decode($original, true) : $original;

        foreach ((array) $original as $option) {
            if (($option['key'] ?? null) === $key && is_array($option['label'] ?? null)) {
                return $option['label'];
            }
        }

        return [];
    }

    /**
     * @param  Builder<CustomField>  $query
     */
    public function scopeForEntity(Builder $query, string $entity): void
    {
        $query->where('entity', $entity)->orderBy('position')->orderBy('id');
    }

    /**
     * Možnosti výberu: kľúč => preložený názov.
     *
     * @return array<string, string>
     */
    public function optionLabels(): array
    {
        $locale = $this->getLocale();

        return collect($this->options ?? [])->mapWithKeys(function ($option) use ($locale) {
            $label = $option['label'];

            if (is_array($label)) {
                $label = $label[$locale] ?? reset($label) ?: $option['key'];
            }

            return [$option['key'] => (string) $label];
        })->all();
    }

    /**
     * Hodnota pripravená na zobrazenie zákazníkovi (null = nezobrazovať).
     */
    public function display(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return null;
        }

        $unit = $this->unit ? ' '.$this->unit : '';

        return match ($this->type) {
            CustomFieldType::Boolean => $value ? __('catalog::shop.yes') : __('catalog::shop.no'),
            CustomFieldType::Select => ($this->optionLabels()[$value] ?? $value).$unit,
            CustomFieldType::MultiSelect => collect((array) $value)->map(fn ($key) => $this->optionLabels()[$key] ?? $key)->implode(', '),
            CustomFieldType::Number => rtrim(rtrim(number_format((float) $value, 6, ',', "\u{00A0}"), '0'), ',').$unit,
            CustomFieldType::Date => Carbon::parse($value)->format(app()->getLocale() === 'en' ? 'j M Y' : 'j. n. Y'),
            default => $value.$unit,
        };
    }

    /** Ukladá sa hodnota v každom jazyku? (iba textové polia) */
    public function storesTranslations(): bool
    {
        return $this->is_translatable && in_array($this->type, [CustomFieldType::Text, CustomFieldType::Textarea], true);
    }
}
