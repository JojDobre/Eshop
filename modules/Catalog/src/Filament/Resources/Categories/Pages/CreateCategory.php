<?php

namespace Modules\Catalog\Filament\Resources\Categories\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\HandlesCustomFields;
use Modules\Catalog\Filament\Resources\Categories\CategoryResource;

class CreateCategory extends CreateRecord
{
    use HandlesCustomFields;

    protected static string $resource = CategoryResource::class;

    protected function customFieldEntity(): string
    {
        return CustomField::CATEGORY;
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->withoutCustomFields($data);
    }

    protected function afterCreate(): void
    {
        $this->saveCustomFields($this->getRecord());
    }
}
