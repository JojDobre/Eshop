<?php

namespace Modules\Catalog\CustomFields;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

/**
 * Polia formulára administrácie vygenerované z definícií vlastných polí.
 * Hodnoty sú vo formulári pod kľúčom „custom_fields".
 */
class CustomFieldsForm
{
    /**
     * @return list<Field>
     */
    public static function components(string $entity): array
    {
        return app(CustomFieldValues::class)->fields($entity)
            ->map(fn (CustomField $field) => self::component($field))
            ->values()
            ->all();
    }

    private static function component(CustomField $field): Field
    {
        $name = 'custom_fields.'.$field->key;

        $component = match ($field->type) {
            CustomFieldType::Textarea => Textarea::make($name)->rows(3)->maxLength(10000),
            CustomFieldType::Number => TextInput::make($name)->rule('regex:/^-?\d+([.,]\d+)?$/')->inputMode('decimal')->suffix($field->unit),
            CustomFieldType::Boolean => Toggle::make($name),
            CustomFieldType::Select => Select::make($name)->options($field->optionLabels())->native(false),
            CustomFieldType::MultiSelect => Select::make($name)->options($field->optionLabels())->multiple(),
            CustomFieldType::Date => DatePicker::make($name),
            CustomFieldType::Color => ColorPicker::make($name)->hex(),
            CustomFieldType::Url => TextInput::make($name)->url()->maxLength(255),
            default => TextInput::make($name)->maxLength(255)->suffix($field->unit),
        };

        return $component
            ->label($field->name)
            ->helperText($field->help)
            ->required($field->is_required && $field->type !== CustomFieldType::Boolean);
    }
}
