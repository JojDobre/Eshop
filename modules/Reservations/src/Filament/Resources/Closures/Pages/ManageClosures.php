<?php

namespace Modules\Reservations\Filament\Resources\Closures\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Reservations\Filament\Resources\Closures\ClosureResource;

class ManageClosures extends ManageRecords
{
    protected static string $resource = ClosureResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
