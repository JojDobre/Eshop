<?php

namespace Modules\Returns\Filament\Resources\ReturnRequests\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Returns\Actions\ChangeReturnStatus;
use Modules\Returns\Filament\Resources\ReturnRequests\ReturnRequestResource;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\ReturnStatus;

/**
 * @property ReturnRequest $record
 */
class ViewReturnRequest extends ViewRecord
{
    protected static string $resource = ReturnRequestResource::class;

    protected function getHeaderActions(): array
    {
        $t = fn (string $key) => __('returns::admin.'.$key);

        return [
            Action::make('changeStatus')
                ->label($t('change_status'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->fillForm(fn () => ['status' => $this->record->status, 'resolution' => $this->record->resolution, 'notify' => true])
                ->schema([
                    Select::make('status')->label($t('status'))->options(ReturnStatus::class)->required()->native(false),
                    Textarea::make('resolution')->label($t('resolution'))->helperText($t('resolution_help'))->rows(3)->maxLength(2000),
                    Toggle::make('notify')->label($t('notify_customer'))->default(true),
                ])
                ->action(function (array $data, ChangeReturnStatus $change) use ($t): void {
                    $status = $data['status'] instanceof ReturnStatus ? $data['status'] : ReturnStatus::from($data['status']);
                    $change->handle($this->record, $status, $data['resolution'] ?? null, (bool) ($data['notify'] ?? true));
                    $this->record->refresh();
                    Notification::make()->success()->title($t('status_changed'))->send();
                }),
        ];
    }
}
