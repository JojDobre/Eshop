<?php

namespace Modules\Tax\Filament\Resources\TaxRates\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Tax\Filament\Resources\TaxRates\TaxRateResource;

class ManageTaxRates extends ManageRecords
{
    protected static string $resource = TaxRateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
