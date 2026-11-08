<?php

namespace Modules\Orders\Filament\Resources\Orders\Pages;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Modules\Orders\Actions\AddOrderNote;
use Modules\Orders\Actions\ChangeOrderStatus;
use Modules\Orders\Actions\IssueDownloads;
use Modules\Orders\Actions\SetPaymentStatus;
use Modules\Orders\CheckoutException;
use Modules\Orders\Filament\Resources\Orders\OrderResource;
use Modules\Orders\Filament\ShipmentActions;
use Modules\Orders\Models\Order;
use Modules\Orders\OrderStatus;
use Modules\Orders\PaymentStatus;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return __('orders::admin.label').' '.$this->order()->number;
    }

    protected function getHeaderActions(): array
    {
        $t = fn (string $key) => __('orders::admin.actions.'.$key);

        return [
            ShipmentActions::create(fn () => $this->order()),
            ShipmentActions::labels(fn () => $this->order()),
            Action::make('sendDownloads')
                ->label(__('orders::admin.downloads.send'))
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn () => IssueDownloads::hasDigitalItems($this->order()))
                ->requiresConfirmation()
                ->modalDescription(__('orders::admin.downloads.send_help'))
                ->action(function (IssueDownloads $downloads): void {
                    $downloads->handle($this->order());
                    Notification::make()->success()->title(__('orders::admin.downloads.sent'))->send();
                    $this->record = $this->order()->fresh(['items.order', 'history.user']);
                }),
            Action::make('changeStatus')
                ->label($t('change_status'))
                ->icon(Heroicon::OutlinedArrowPath)
                ->schema([
                    Select::make('status')->label($t('new_status'))
                        ->options(OrderStatus::class)
                        ->default(fn () => $this->order()->status)
                        ->required()
                        ->native(false),
                    Toggle::make('notify')->label($t('notify_customer'))->helperText($t('notify_customer_help'))->default(true),
                ])
                ->action(function (array $data, ChangeOrderStatus $change) use ($t): void {
                    try {
                        $change->handle(
                            $this->order(),
                            OrderStatus::from($data['status'] instanceof OrderStatus ? $data['status']->value : $data['status']),
                            $this->user(),
                            (bool) ($data['notify'] ?? true),
                        );
                    } catch (CheckoutException $e) {
                        Notification::make()->danger()->title($e->getMessage())->send();

                        return;
                    }

                    Notification::make()->success()->title($t('status_changed'))->send();
                    $this->refreshOrder();
                }),
            Action::make('markPaid')
                ->label($t('mark_paid'))
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => ! $this->order()->isPaid())
                ->action(fn (SetPaymentStatus $setPaymentStatus) => $this->setPayment($setPaymentStatus, PaymentStatus::Paid)),
            Action::make('addNote')
                ->label($t('add_note'))
                ->icon(Heroicon::OutlinedChatBubbleLeftEllipsis)
                ->color('gray')
                ->schema([
                    Textarea::make('note')->label($t('note'))->helperText($t('note_help'))->required()->maxLength(2000)->rows(4),
                ])
                ->action(function (array $data, AddOrderNote $addNote) use ($t): void {
                    $addNote->handle($this->order(), $data['note'], $this->user());
                    Notification::make()->success()->title($t('note_added'))->send();
                    $this->refreshOrder();
                }),
            ActionGroup::make([
                Action::make('markUnpaid')
                    ->label($t('mark_unpaid'))
                    ->icon(Heroicon::OutlinedXCircle)
                    ->requiresConfirmation()
                    ->visible(fn () => $this->order()->isPaid())
                    ->action(fn (SetPaymentStatus $setPaymentStatus) => $this->setPayment($setPaymentStatus, PaymentStatus::Unpaid)),
                Action::make('customerPage')
                    ->label($t('view_customer_page'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn () => $this->order()->publicUrl(), shouldOpenInNewTab: true),
            ]),
        ];
    }

    private function setPayment(SetPaymentStatus $action, PaymentStatus $status): void
    {
        $action->handle($this->order(), $status, $this->user());
        Notification::make()->success()->title(__('orders::admin.actions.payment_changed'))->send();
        $this->refreshOrder();
    }

    private function order(): Order
    {
        /** @var Order $order */
        $order = $this->getRecord();

        return $order;
    }

    private function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    private function refreshOrder(): void
    {
        $this->record = $this->order()->fresh(['items.order', 'history.user']);

        // Detail objednávky sa vykreslí znova s novými údajmi (stav, platba, história).
        $this->cachedSchemas = [];
    }
}
