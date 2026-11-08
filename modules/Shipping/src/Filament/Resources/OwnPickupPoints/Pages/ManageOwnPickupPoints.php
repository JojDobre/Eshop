<?php

namespace Modules\Shipping\Filament\Resources\OwnPickupPoints\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Str;
use Modules\Shipping\Carriers\OwnPointsCarrier;
use Modules\Shipping\Filament\Resources\OwnPickupPoints\OwnPickupPointResource;

class ManageOwnPickupPoints extends ManageRecords
{
    protected static string $resource = OwnPickupPointResource::class;

    protected function getHeaderActions(): array
    {
        // Kód miesta sa vytvorí sám (nemení sa ani pri premenovaní, aby staré objednávky sedeli).
        return [CreateAction::make()->mutateDataUsing(fn (array $data) => [
            ...$data,
            'carrier' => OwnPointsCarrier::KEY,
            'code' => Str::lower(Str::random(10)),
            'type' => 'own',
            'synced_at' => now(),
        ])];
    }
}
