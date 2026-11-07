<?php

namespace Modules\Catalog\Filament\Resources\Manufacturers\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Catalog\Filament\Resources\Manufacturers\ManufacturerResource;
use Modules\Catalog\Models\Manufacturer;

class ManageManufacturers extends ManageRecords
{
    protected static string $resource = ManufacturerResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->using(fn (array $data) => ManufacturerResource::saveWithCustomFields(new Manufacturer, $data))];
    }
}
