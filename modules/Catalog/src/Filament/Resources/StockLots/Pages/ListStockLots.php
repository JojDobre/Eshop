<?php

namespace Modules\Catalog\Filament\Resources\StockLots\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Catalog\Filament\Resources\StockLots\StockLotResource;
use Modules\Catalog\Filament\StockLotTable;

class ListStockLots extends ListRecords
{
    protected static string $resource = StockLotResource::class;

    protected function getHeaderActions(): array
    {
        return [StockLotTable::receiveAction()];
    }
}
