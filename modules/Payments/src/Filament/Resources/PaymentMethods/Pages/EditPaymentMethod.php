<?php

namespace Modules\Payments\Filament\Resources\PaymentMethods\Pages;

use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Payments\Filament\Resources\PaymentMethods\PaymentMethodResource;

class EditPaymentMethod extends EditRecord
{
    use FillsContentTranslations;

    protected static string $resource = PaymentMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
