<?php

namespace Modules\Catalog\Filament\Resources\Warehouses\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Catalog\Filament\Resources\Warehouses\WarehouseResource;
use Modules\Catalog\Models\Warehouse;

class ManageWarehouses extends ManageRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data) => [...$data, 'position' => (int) Warehouse::query()->max('position') + 1])];
    }
}
