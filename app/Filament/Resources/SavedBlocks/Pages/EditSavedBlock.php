<?php

namespace App\Filament\Resources\SavedBlocks\Pages;

use App\Filament\Concerns\FillsContentTranslations;
use App\Filament\Resources\SavedBlocks\SavedBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSavedBlock extends EditRecord
{
    use FillsContentTranslations;

    protected static string $resource = SavedBlockResource::class;

    public function getSubheading(): ?string
    {
        return trans_choice('admin.saved_blocks.used_in', $count = SavedBlockResource::usageCount($this->getRecord()), ['count' => $count]);
    }

    protected function getHeaderActions(): array
    {
        return [...$this->localeSwitcherActions(), DeleteAction::make()];
    }
}
