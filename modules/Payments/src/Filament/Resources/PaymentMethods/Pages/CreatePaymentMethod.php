<?php

namespace Modules\Payments\Filament\Resources\PaymentMethods\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Payments\Filament\Resources\PaymentMethods\PaymentMethodResource;

class CreatePaymentMethod extends CreateRecord
{
    protected static string $resource = PaymentMethodResource::class;
}
