<?php

namespace App\Filament\Resources\SavedBlocks\Pages;

use App\Filament\Resources\SavedBlocks\SavedBlockResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSavedBlocks extends ListRecords
{
    protected static string $resource = SavedBlockResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.saved_blocks.intro');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
