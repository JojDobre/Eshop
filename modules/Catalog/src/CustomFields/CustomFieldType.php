<?php

namespace Modules\Catalog\CustomFields;

use Filament\Support\Contracts\HasLabel;

enum CustomFieldType: string implements HasLabel
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Boolean = 'boolean';
    case Select = 'select';
    case MultiSelect = 'multiselect';
    case Date = 'date';
    case Color = 'color';
    case Url = 'url';

    public function getLabel(): string
    {
        return __('catalog::admin.custom_fields.types.'.$this->value);
    }

    /**
     * Stĺpec, v ktorom sa hodnota ukladá.
     */
    public function column(): string
    {
        return match ($this) {
            self::Textarea => 'value_text',
            self::Number => 'value_number',
            self::Boolean => 'value_boolean',
            self::MultiSelect => 'value_json',
            default => 'value_string',
        };
    }

    public function hasOptions(): bool
    {
        return $this === self::Select || $this === self::MultiSelect;
    }

    /**
     * Dá sa podľa poľa filtrovať v kategórii?
     */
    public function isFilterable(): bool
    {
        return in_array($this, [self::Select, self::MultiSelect, self::Boolean, self::Number, self::Color], true);
    }
}
