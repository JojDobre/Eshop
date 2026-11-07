<?php

namespace Modules\Catalog\Filament\Resources\Addons\Pages;

use App\Core\Support\CurrencyContext;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Modules\Catalog\Filament\Resources\Addons\AddonResource;

class ManageAddons extends ManageRecords
{
    protected static string $resource = AddonResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data) => [...$data, 'currency' => app(CurrencyContext::class)->current()])];
    }
}
