<?php

namespace Modules\Reservations\Filament\Resources\BookingResources\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Reservations\Filament\Resources\BookingResources\BookingResourceResource;

class ManageBookingResources extends ManageRecords
{
    protected static string $resource = BookingResourceResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
