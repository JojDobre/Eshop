<?php

namespace Modules\Orders\Filament\Resources\Orders\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Orders\Filament\Resources\Orders\OrderResource;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;
}
