<?php

namespace Modules\Orders\Filament\Resources\GiftVouchers\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Support\Icons\Heroicon;
use Modules\Orders\Filament\Resources\GiftVouchers\GiftVoucherResource;
use Modules\Orders\GiftVouchers;

class ManageGiftVouchers extends ManageRecords
{
    protected static string $resource = GiftVoucherResource::class;

    protected function getHeaderActions(): array
    {
        $t = fn (string $key, array $replace = []) => __('orders::admin.vouchers.'.$key, $replace);

        return [
            Action::make('issue')->label($t('issue'))->icon(Heroicon::OutlinedPlus)
                ->schema(GiftVoucherResource::issueFields())->modalWidth('lg')
                ->action(function (array $data) use ($t): void {
                    $vouchers = app(GiftVouchers::class);
                    $voucher = $vouchers->create((int) $data['amount'], $data['currency'], email: $data['recipient_email'] ?: null, note: $data['note'] ?: null);

                    if (! empty($data['send']) && filled($data['recipient_email'])) {
                        $vouchers->send([$voucher], $data['recipient_email'], null);
                    }

                    Notification::make()->success()->title($t('issued', ['code' => $voucher->formattedCode()]))->persistent()->send();
                }),
        ];
    }
}
