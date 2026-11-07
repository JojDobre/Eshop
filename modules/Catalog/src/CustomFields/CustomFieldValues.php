<?php

namespace Modules\Catalog\CustomFields;

use App\Core\Content\ContentLocale;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Načítanie a uloženie hodnôt vlastných polí objektu.
 */
class CustomFieldValues
{
    /**
     * @return Collection<int, CustomField>
     */
    public function fields(string $entity): Collection
    {
        return CustomField::query()->forEntity($entity)->get();
    }

    /**
     * Hodnoty objektu: kľúč poľa => hodnota. Preložiteľné polia v zadanom jazyku
     * (null = jazyk obsahu, s náhradou za predvolený jazyk).
     *
     * @return array<string, mixed>
     */
    public function get(string $entity, int $id, ?string $locale = null, bool $fallback = true): array
    {
        return CustomFieldValue::query()
            ->with('field')
            ->where('entity_type', $entity)
            ->where('entity_id', $id)
            ->get()
            ->filter(fn (CustomFieldValue $value) => $value->field !== null)
            ->mapWithKeys(fn (CustomFieldValue $value) => [$value->field->key => $value->valueIn($locale, $fallback)])
            ->all();
    }

    /**
     * Uloží hodnoty (kľúč poľa => hodnota). Prázdna hodnota sa zmaže.
     *
     * @param  array<string, mixed>  $values
     */
    public function save(string $entity, int $id, array $values, ?string $locale = null): void
    {
        DB::transaction(function () use ($entity, $id, $values, $locale): void {
            foreach ($this->fields($entity) as $field) {
                if (! array_key_exists($field->key, $values)) {
                    continue;
                }

                $value = $this->normalize($field, $values[$field->key]);
                $where = ['custom_field_id' => $field->id, 'entity_type' => $entity, 'entity_id' => $id];

                // Preložiteľné pole: hodnota sa uloží do zvoleného jazyka, ostatné jazyky ostanú.
                if ($field->storesTranslations()) {
                    $existing = CustomFieldValue::query()->where($where)->first();
                    $translations = array_filter([...(array) ($existing?->value_json ?? []), ($locale ?? app(ContentLocale::class)->current()) => $value], fn ($text) => filled($text));

                    if ($translations === []) {
                        CustomFieldValue::query()->where($where)->delete();
                    } else {
                        CustomFieldValue::query()->updateOrCreate($where, [
                            'value_string' => null, 'value_text' => null, 'value_number' => null, 'value_boolean' => null, 'value_json' => $translations,
                        ]);
                    }

                    continue;
                }

                if ($value === null) {
                    CustomFieldValue::query()->where($where)->delete();

                    continue;
                }

                CustomFieldValue::query()->updateOrCreate($where, [
                    'value_string' => null, 'value_text' => null, 'value_number' => null, 'value_boolean' => null, 'value_json' => null,
                    $field->type->column() => $value,
                ]);
            }
        });
    }

    private function normalize(CustomField $field, mixed $value): mixed
    {
        if ($value === '' || $value === [] || $value === null) {
            return $field->type === CustomFieldType::Boolean && $value !== null && $value !== '' ? false : null;
        }

        return match ($field->type) {
            CustomFieldType::Number => is_numeric(str_replace(',', '.', (string) $value)) ? (float) str_replace(',', '.', (string) $value) : null,
            CustomFieldType::Boolean => (bool) $value,
            CustomFieldType::MultiSelect => array_values(array_intersect((array) $value, array_keys($field->optionLabels()))) ?: null,
            CustomFieldType::Select => array_key_exists((string) $value, $field->optionLabels()) ? (string) $value : null,
            default => mb_substr(trim((string) $value), 0, $field->type === CustomFieldType::Textarea ? 10000 : 255),
        };
    }
}
