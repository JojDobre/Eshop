<?php

namespace Modules\Forms\Filament\Resources\Forms\Pages;

use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Forms\Filament\Resources\Forms\FormResource;

class EditForm extends EditRecord
{
    use FillsContentTranslations;

    protected static string $resource = FormResource::class;

    protected function getHeaderActions(): array
    {
        return [...$this->localeSwitcherActions(), DeleteAction::make()];
    }
}
