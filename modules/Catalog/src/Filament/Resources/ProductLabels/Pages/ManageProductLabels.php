<?php

namespace Modules\Catalog\Filament\Resources\ProductLabels\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Catalog\Filament\Resources\ProductLabels\ProductLabelResource;

class ManageProductLabels extends ManageRecords
{
    protected static string $resource = ProductLabelResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
