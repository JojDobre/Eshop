<?php

namespace Modules\Epr;

use App\Core\Modules\ModuleServiceProvider;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Modules\Catalog\Filament\Resources\Products\ProductResource;

class EprServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'epr';
    }

    public function boot(): void
    {
        parent::boot();

        // Obal produktu vo formulári produktu.
        ProductResource::addFormSection('epr', fn () => Section::make(__('epr::admin.packaging'))
            ->description(__('epr::admin.packaging_help'))
            ->collapsed()
            ->columns(4)
            ->schema(array_map(
                fn (string $material) => TextInput::make('packaging.'.$material)->label(__('epr::admin.materials.'.$material))
                    ->numeric()->minValue(0)->maxValue(1_000_000)->suffix('g'),
                Packaging::MATERIALS,
            )));
    }
}
