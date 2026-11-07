<?php

namespace Modules\Pages\Filament\Resources\Pages\Pages;

use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Pages\Filament\Resources\Pages\PageResource;
use Modules\Pages\Models\Page;

class EditPage extends EditRecord
{
    use FillsContentTranslations;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        /** @var Page $page */
        $page = $this->getRecord();

        return [
            ...$this->localeSwitcherActions(),
            Action::make('view')->label(__('pages::admin.view'))
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)->color('gray')
                ->url(fn () => $page->url(), shouldOpenInNewTab: true)
                ->visible(fn () => $page->is_visible),
            DeleteAction::make(),
        ];
    }
}
