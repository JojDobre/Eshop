<?php

namespace Modules\Catalog\CustomFields;

use Filament\Schemas\Components\Section;
use Modules\Catalog\CatalogFeatures;

/**
 * Sekcia vlastných polí do formulára kategórie, výrobcu alebo zákazníka
 * (zobrazí sa iba so zapnutou funkciou a ak pre objekt existujú polia).
 */
final class CustomFieldsSection
{
    public static function make(string $entity): Section
    {
        return Section::make(__('catalog::admin.custom_fields.plural'))
            ->schema(fn () => CustomFieldsForm::components($entity))
            ->columns(2)
            ->visible(fn () => CatalogFeatures::enabled('extra_custom_fields') && CustomField::query()->forEntity($entity)->exists());
    }
}
