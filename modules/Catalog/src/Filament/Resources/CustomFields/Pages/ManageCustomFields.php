<?php

namespace Modules\Catalog\Filament\Resources\CustomFields\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\Filament\Resources\CustomFields\CustomFieldResource;

class ManageCustomFields extends ManageRecords
{
    protected static string $resource = CustomFieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->mutateDataUsing(function (array $data): array {
                $data['entity'] = CustomField::PRODUCT;

                return $data;
            }),
        ];
    }
}
