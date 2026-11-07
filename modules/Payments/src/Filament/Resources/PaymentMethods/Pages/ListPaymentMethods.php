<?php

namespace Modules\Payments\Filament\Resources\PaymentMethods\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Modules\Payments\Filament\Resources\PaymentMethods\PaymentMethodResource;

class ListPaymentMethods extends ListRecords
{
    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
