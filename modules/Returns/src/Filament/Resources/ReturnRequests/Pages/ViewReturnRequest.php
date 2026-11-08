<?php

namespace Modules\Returns\Filament\Resources\ReturnRequests\Pages;

use App\Filament\Forms\MoneyInput;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Invoices\IssueInvoice;
use Modules\Returns\Actions\ChangeReturnStatus;
use Modules\Returns\Filament\Resources\ReturnRequests\ReturnRequestResource;
use Modules\Returns\Models\ReturnRequest;
use Modules\Returns\ReturnOutcome;
use Modules\Returns\ReturnProtocol;
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
                ->fillForm(fn () => ['status' => $this->record->status, 'resolution' => $this->record->resolution, 'outcome' => $this->record->outcome,
                    'refund_amount' => $this->record->refund_amount, 'notify' => true])
                ->schema([
                    Select::make('status')->label($t('status'))->options(ReturnStatus::class)->required()->native(false),
                    Select::make('outcome')->label($t('outcome'))->options(ReturnOutcome::class)->native(false)->live(),
                    MoneyInput::make('refund_amount', $this->record->order->currency)->label($t('refund_amount'))
                        ->visible(fn ($get) => in_array($get('outcome') instanceof ReturnOutcome ? $get('outcome')->value : $get('outcome'), ['refunded', 'discount'], true)),
                    Textarea::make('resolution')->label($t('resolution'))->helperText($t('resolution_help'))->rows(3)->maxLength(2000),
                    Toggle::make('notify')->label($t('notify_customer'))->default(true),
                ])
                ->action(function (array $data, ChangeReturnStatus $change) use ($t): void {
                    $status = $data['status'] instanceof ReturnStatus ? $data['status'] : ReturnStatus::from($data['status']);
                    $outcome = $data['outcome'] ?? null;
                    $this->record->update([
                        'outcome' => $outcome instanceof ReturnOutcome ? $outcome->value : ($outcome ?: null),
                        'refund_amount' => $data['refund_amount'] ?? null,
                    ]);
                    $change->handle($this->record, $status, $data['resolution'] ?? null, (bool) ($data['notify'] ?? true));
                    $this->record->refresh();
                    Notification::make()->success()->title($t('status_changed'))->send();
                }),
            // Protokol o reklamácii / odstúpení na tlač a podpis.
            Action::make('protocol')->label($t('protocol'))->icon(Heroicon::OutlinedDocumentText)->color('gray')
                ->action(fn () => response()->streamDownload(
                    fn () => print (ReturnProtocol::pdf($this->record)),
                    'protokol-'.$this->record->number.'.pdf',
                    ['Content-Type' => 'application/pdf'],
                )),
            // Dobropis k faktúre objednávky (ak je zapnutý modul dokladov).
            Action::make('creditNote')->label($t('credit_note'))->icon(Heroicon::OutlinedReceiptRefund)->color('gray')
                ->visible(fn () => ReturnProtocol::invoiceFor($this->record) !== null && $this->record->credit_note_id === null)
                ->requiresConfirmation()->modalDescription($t('credit_note_help'))
                ->action(function () use ($t): void {
                    $creditNote = app(IssueInvoice::class)->creditNote(ReturnProtocol::invoiceFor($this->record));
                    $this->record->update(['credit_note_id' => $creditNote->id]);
                    Notification::make()->success()->title($t('credit_note_created', ['number' => $creditNote->number]))->send();
                }),
        ];
    }
}
