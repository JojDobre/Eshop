<?php

namespace Modules\Catalog\CustomFields;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Models\Product;

/**
 * Filtre zoznamu produktov podľa vlastných polí (parametre označené ako filtrovateľné).
 *
 * Vstup z adresy: f[farba][]=cervena, f[vykon][min]=100, f[vodeodolne]=1
 */
class CustomFieldFilters
{
    /**
     * Dostupné filtre pre produkty z daného dopytu (iba hodnoty, ktoré sa medzi produktmi naozaj vyskytujú).
     *
     * @param  Builder<Product>  $products
     * @return list<array{field: CustomField, options?: array<string, string>, min?: float, max?: float}>
     */
    public function available(Builder $products): array
    {
        $fields = CustomField::query()->forEntity(CustomField::PRODUCT)->where('is_filterable', true)->get();

        if ($fields->isEmpty()) {
            return [];
        }

        $ids = (clone $products)->reorder()->select('products.id')->toBase();
        $filters = [];

        foreach ($fields as $field) {
            $values = DB::table('custom_field_values')
                ->where('custom_field_id', $field->id)
                ->where('entity_type', CustomField::PRODUCT)
                ->whereIn('entity_id', $ids);

            $filter = match ($field->type) {
                CustomFieldType::Select, CustomFieldType::Color => $this->options($field, (clone $values)->distinct()->pluck('value_string')->all()),
                CustomFieldType::MultiSelect => $this->options($field, (clone $values)->pluck('value_json')
                    ->flatMap(fn ($json) => (array) json_decode((string) $json, true))->unique()->all()),
                CustomFieldType::Boolean => (clone $values)->where('value_boolean', true)->exists() ? ['field' => $field] : null,
                CustomFieldType::Number => $this->range($field, (clone $values)->min('value_number'), (clone $values)->max('value_number')),
                default => null,
            };

            if ($filter !== null) {
                $filters[] = $filter;
            }
        }

        return $filters;
    }

    /**
     * Použije zvolené filtre na dopyt.
     *
     * @param  Builder<Product>  $products
     * @param  array<string, mixed>  $input
     */
    public function apply(Builder $products, array $input): void
    {
        if ($input === []) {
            return;
        }

        $fields = CustomField::query()->forEntity(CustomField::PRODUCT)->where('is_filterable', true)
            ->whereIn('key', array_keys($input))->get();

        foreach ($fields as $field) {
            $value = $input[$field->key];

            $products->whereExists(function (QueryBuilder $query) use ($field, $value): void {
                $query->select(DB::raw(1))->from('custom_field_values')
                    ->whereColumn('custom_field_values.entity_id', 'products.id')
                    ->where('custom_field_values.entity_type', CustomField::PRODUCT)
                    ->where('custom_field_values.custom_field_id', $field->id);

                match ($field->type) {
                    CustomFieldType::Select, CustomFieldType::Color => $query->whereIn('value_string', array_map('strval', (array) $value)),
                    CustomFieldType::MultiSelect => $query->where(function (QueryBuilder $any) use ($value): void {
                        foreach ((array) $value as $key) {
                            $any->orWhereJsonContains('value_json', (string) $key);
                        }
                    }),
                    CustomFieldType::Boolean => $query->where('value_boolean', true),
                    CustomFieldType::Number => $query
                        ->when(is_numeric($value['min'] ?? null), fn ($q) => $q->where('value_number', '>=', (float) $value['min']))
                        ->when(is_numeric($value['max'] ?? null), fn ($q) => $q->where('value_number', '<=', (float) $value['max'])),
                    default => null,
                };
            });
        }
    }

    /**
     * Vyčistí vstup z adresy (iba známe kľúče a jednoduché hodnoty).
     *
     * @return array<string, mixed>
     */
    public function input(mixed $raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        return collect($raw)
            ->filter(fn ($value, $key) => is_string($key) && preg_match('/^[a-z0-9_-]+$/', $key))
            ->map(fn ($value) => is_array($value) ? array_filter($value, fn ($item) => is_scalar($item) && $item !== '') : (is_scalar($value) ? (string) $value : null))
            ->filter(fn ($value) => $value !== null && $value !== '' && $value !== [])
            ->all();
    }

    /**
     * @param  list<mixed>  $present
     * @return array{field: CustomField, options: array<string, string>}|null
     */
    private function options(CustomField $field, array $present): ?array
    {
        $present = array_map('strval', array_filter($present, fn ($value) => $value !== null && $value !== ''));

        $options = $field->type === CustomFieldType::Color
            ? array_combine($present, $present)
            : array_intersect_key($field->optionLabels(), array_flip($present));

        return $options === [] ? null : ['field' => $field, 'options' => $options];
    }

    /**
     * @return array{field: CustomField, min: float, max: float}|null
     */
    private function range(CustomField $field, mixed $min, mixed $max): ?array
    {
        if ($min === null || $max === null || (float) $min === (float) $max) {
            return null;
        }

        return ['field' => $field, 'min' => floor((float) $min), 'max' => ceil((float) $max)];
    }
}
