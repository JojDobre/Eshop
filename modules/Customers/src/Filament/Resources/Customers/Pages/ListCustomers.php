<?php

namespace Modules\Customers\Filament\Resources\Customers\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Customers\Filament\Resources\Customers\CustomerResource;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;
}
