<?php

namespace Modules\Customers\Filament\Resources\CustomerGroups\Pages;

use App\Models\CustomerGroup;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Customers\Filament\Resources\CustomerGroups\CustomerGroupResource;

class ManageCustomerGroups extends ManageRecords
{
    protected static string $resource = CustomerGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data) => [...$data, 'position' => (int) CustomerGroup::query()->max('position') + 1])];
    }
}
