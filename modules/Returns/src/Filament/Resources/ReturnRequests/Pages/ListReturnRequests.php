<?php

namespace Modules\Returns\Filament\Resources\ReturnRequests\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\Returns\Filament\Resources\ReturnRequests\ReturnRequestResource;

class ListReturnRequests extends ListRecords
{
    protected static string $resource = ReturnRequestResource::class;
}
