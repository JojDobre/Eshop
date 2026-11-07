<?php

namespace Modules\Catalog\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use App\Settings\CatalogSettings;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageCatalogSettings extends BaseSettingsPage
{
    protected static string $settings = CatalogSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?int $navigationSort = 15;

    protected static ?string $slug = 'settings/catalog';

    public static function getNavigationLabel(): string
    {
        return __('catalog::admin.settings.title');
    }

    public function getTitle(): string
    {
        return __('catalog::admin.settings.title');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextInput::make('low_stock_threshold')
                    ->label(__('catalog::admin.settings.low_stock_threshold'))
                    ->helperText(__('catalog::admin.settings.low_stock_threshold_help'))
                    ->integer()->minValue(0)->required()->suffix(__('catalog::admin.pieces')),
            ]),
        ]);
    }
}
