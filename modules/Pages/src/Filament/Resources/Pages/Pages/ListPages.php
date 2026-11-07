<?php

namespace Modules\Pages\Filament\Resources\Pages\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Pages\Filament\Resources\Pages\PageResource;

class ListPages extends ListRecords
{
    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
