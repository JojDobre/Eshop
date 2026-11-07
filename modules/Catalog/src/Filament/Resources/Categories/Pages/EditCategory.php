<?php

namespace Modules\Catalog\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Catalog\CustomFields\CustomField;
use Modules\Catalog\CustomFields\HandlesCustomFields;
use Modules\Catalog\Filament\Resources\Categories\CategoryResource;

class EditCategory extends EditRecord
{
    use FillsContentTranslations, HandlesCustomFields;

    protected static string $resource = CategoryResource::class;

    protected function customFieldEntity(): string
    {
        return CustomField::CATEGORY;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->withCustomFields($this->fillContentTranslations($data), $this->getRecord());
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->withoutCustomFields($data);
    }

    protected function afterSave(): void
    {
        $this->saveCustomFields($this->getRecord());
    }

    protected function getHeaderActions(): array
    {
        return [...$this->localeSwitcherActions(), DeleteAction::make()];
    }
}
