<?php

namespace Modules\Catalog\Filament\Resources\ProductTypes\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Catalog\Filament\Resources\ProductTypes\ProductTypeResource;

class ManageProductTypes extends ManageRecords
{
    protected static string $resource = ProductTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
