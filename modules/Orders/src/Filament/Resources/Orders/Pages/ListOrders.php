<?php

namespace Modules\Orders\Filament\Resources\Orders\Pages;

use App\Filament\Concerns\HasStatusTabs;
use Filament\Resources\Pages\ListRecords;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\OrderStatus;

class ListOrders extends ListRecords
{
    use HasStatusTabs;

    protected static string $resource = OrderResource::class;

    protected function statusTabs(): array
    {
        return self::columnTabs('status', OrderStatus::cases());
    }
}
