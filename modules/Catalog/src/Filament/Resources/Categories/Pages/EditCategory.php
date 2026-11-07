<?php

namespace Modules\Catalog\Filament\Resources\Categories\Pages;

use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Catalog\Filament\Resources\Categories\CategoryResource;

class EditCategory extends EditRecord
{
    use FillsContentTranslations;

    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
