<?php

namespace Modules\Catalog\CustomFields;

use App\Core\Content\ContentLocale;
use App\Core\Content\ShopLocales;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $custom_field_id
 * @property string $entity_type
 * @property int $entity_id
 * @property-read CustomField $field
 */
class CustomFieldValue extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value_number' => 'float',
            'value_boolean' => 'boolean',
            'value_json' => 'array',
        ];
    }

    /**
     * @return BelongsTo<CustomField, $this>
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    public function value(): mixed
    {
        return $this->getAttribute($this->field->type->column());
    }

    /**
     * Hodnota v jazyku (preložiteľné textové polia); null = jazyk obsahu s náhradou za predvolený jazyk.
     */
    public function valueIn(?string $locale = null, bool $fallback = true): mixed
    {
        if (! $this->field->storesTranslations()) {
            return $this->value();
        }

        $translations = (array) ($this->value_json ?? []);
        $locale ??= app(ContentLocale::class)->current();

        if (filled($translations[$locale] ?? null) || ! $fallback) {
            return $translations[$locale] ?? null;
        }

        return $translations[app(ShopLocales::class)->default()] ?? collect($translations)->filter()->first();
    }
}
