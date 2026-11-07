<?php

namespace Modules\Shipping\Filament\Resources\ShippingMethods\Pages;

use App\Filament\Concerns\FillsContentTranslations;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;

class EditShippingMethod extends EditRecord
{
    use FillsContentTranslations;

    protected static string $resource = ShippingMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [...$this->localeSwitcherActions(), DeleteAction::make()];
    }
}
