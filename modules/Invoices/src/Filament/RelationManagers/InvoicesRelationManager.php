<?php

namespace Modules\Invoices\Filament\RelationManagers;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use Modules\Invoices\InvoiceType;
use Modules\Invoices\IssueInvoice;
use Modules\Invoices\Mail\InvoiceMail;
use Modules\Invoices\Models\Invoice;
use Modules\Orders\Models\Order;
use Throwable;

/**
 * Doklady objednávky v jej detaile: vystavenie, stiahnutie a odoslanie e-mailom.
 */
class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('invoices::admin.plural');
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        $t = fn (string $key) => __('invoices::admin.'.$key);

        return $table
            ->columns([
                TextColumn::make('number')->label($t('number'))->weight('medium'),
                TextColumn::make('type')->label($t('type'))->badge(),
                TextColumn::make('issued_at')->label($t('issued_at'))->date(),
                TextColumn::make('total')->label($t('total'))->formatStateUsing(fn (Invoice $record) => format_price($record->total, $record->currency)),
                TextColumn::make('sent_at')->label($t('sent_at'))->dateTime()->placeholder('—'),
            ])
            ->headerActions([
                $this->issueAction(InvoiceType::Proforma),
                $this->issueAction(InvoiceType::Invoice),
            ])
            ->recordActions([
                Action::make('download')->label($t('download'))->icon(Heroicon::OutlinedArrowDownTray)
                    ->url(fn (Invoice $record) => route('invoices.admin', $record), shouldOpenInNewTab: true),
                Action::make('send')->label($t('send'))->icon(Heroicon::OutlinedPaperAirplane)->requiresConfirmation()
                    ->action(fn (Invoice $record) => $this->send($record)),
                Action::make('creditNote')->label($t('issue_credit_note'))->icon(Heroicon::OutlinedReceiptRefund)->color('warning')
                    ->requiresConfirmation()->modalDescription($t('credit_note_help'))
                    ->visible(fn (Invoice $record) => $record->type === InvoiceType::Invoice
                        && ! Invoice::query()->where('related_invoice_id', $record->id)->exists())
                    ->action(function (Invoice $record, IssueInvoice $issue): void {
                        $issue->creditNote($record);
                        Notification::make()->success()->title(__('invoices::admin.issued'))->send();
                    }),
            ])
            ->paginated(false);
    }

    private function issueAction(InvoiceType $type): Action
    {
        return Action::make('issue_'.$type->value)
            ->label(__('invoices::admin.issue', ['type' => $type->getLabel()]))
            ->icon(Heroicon::OutlinedDocumentPlus)
            ->color($type === InvoiceType::Invoice ? 'primary' : 'gray')
            ->requiresConfirmation()
            ->visible(fn () => ! $this->order()->invoices()->where('type', $type)->exists())
            ->action(function (IssueInvoice $issue) use ($type): void {
                $issue->forOrder($this->order(), $type);
                Notification::make()->success()->title(__('invoices::admin.issued'))->send();
            });
    }

    private function send(Invoice $invoice): void
    {
        try {
            Mail::to($invoice->customer['email'], $invoice->customer['name'])->locale($invoice->locale)->send(new InvoiceMail($invoice));
            $invoice->update(['sent_at' => now()]);
        } catch (Throwable $e) {
            Notification::make()->danger()->title(__('invoices::admin.send_failed'))->body($e->getMessage())->send();

            return;
        }

        Notification::make()->success()->title(__('invoices::admin.sent', ['email' => $invoice->customer['email']]))->send();
    }

    private function order(): Order
    {
        /** @var Order $order */
        $order = $this->getOwnerRecord();

        return $order;
    }
}
