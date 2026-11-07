<?php

namespace Modules\Catalog\Filament\Resources\CustomFields\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Catalog\CatalogFeatures;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\Filament\Resources\CustomFields\CustomFieldResource;

class ManageCustomFields extends ManageRecords
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->mutateDataUsing(function (array $data): array {
                // Polia pre ostatné objekty iba so zapnutou funkciou.
                if (! CatalogFeatures::enabled('extra_custom_fields') || ! in_array($data['entity'] ?? null, CustomField::ENTITIES, true)) {
                    $data['entity'] = CustomField::PRODUCT;
                }

                unset($data['editing_locale']);

                return $data;
            }),
        ];
    }
}
