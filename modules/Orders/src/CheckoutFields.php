<?php

namespace Modules\Orders;

use Illuminate\Support\Collection;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\CustomFieldType;
use Modules\Catalog\CustomFields\CustomFieldValues;

/**
 * Vlastné polia objednávky, ktoré vypĺňa zákazník v pokladni (napr. „Text na tortu“, „Želaný dátum doručenia“).
 */
final class CheckoutFields
{
    /**
     * @return Collection<int, CustomField>
     */
    public static function fields(): Collection
    {
        if (! CatalogFeatures::enabled('extra_custom_fields')) {
            return collect();
        }

        return app(CustomFieldValues::class)->fields(CustomField::ORDER);
    }

    /**
     * Pravidlá kontroly pre pole „custom_fields“ v pokladni.
     *
     * @return array<string, mixed>
     */
    public static function rules(): array
    {
        $rules = [];

        foreach (self::fields() as $field) {
            $required = $field->is_required && $field->type !== CustomFieldType::Boolean ? 'required' : 'nullable';
            $rules['custom_fields.'.$field->key] = match ($field->type) {
                CustomFieldType::Number => [$required, 'regex:/^-?\d+([.,]\d+)?$/'],
                CustomFieldType::Date => [$required, 'date'],
                CustomFieldType::Select => [$required, 'in:'.implode(',', array_keys($field->optionLabels()))],
                CustomFieldType::MultiSelect => [$required, 'array'],
                CustomFieldType::Boolean => ['nullable', 'boolean'],
                CustomFieldType::Url => [$required, 'url', 'max:255'],
                CustomFieldType::Textarea => [$required, 'string', 'max:2000'],
                default => [$required, 'string', 'max:255'],
            };
        }

        return $rules;
    }

    /**
     * Vyplnené hodnoty na zobrazenie: [názov => hodnota].
     *
     * @return array<string, string>
     */
    public static function display(int $orderId): array
    {
        $values = app(CustomFieldValues::class)->get(CustomField::ORDER, $orderId);
        $rows = [];

        foreach (app(CustomFieldValues::class)->fields(CustomField::ORDER) as $field) {
            if (($text = $field->display($values[$field->key] ?? null)) !== null) {
                $rows[$field->name] = $text;
            }
        }

        return $rows;
    }
}
