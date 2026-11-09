<?php

namespace Modules\Catalog\Filament\Resources\AvailabilityStatuses\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Catalog\Filament\Resources\AvailabilityStatuses\AvailabilityStatusResource;

class ManageAvailabilityStatuses extends ManageRecords
{
    protected static string $resource = AvailabilityStatusResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
