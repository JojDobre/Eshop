<?php

namespace Modules\Shipping\Filament\Resources\ShippingMethods\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;

class CreateShippingMethod extends CreateRecord
{
    protected static string $resource = ShippingMethodResource::class;
}
