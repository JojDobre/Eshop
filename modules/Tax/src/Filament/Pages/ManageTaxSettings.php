<?php

namespace Modules\Tax\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use App\Settings\TaxSettings;
use BackedEnum;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ManageTaxSettings extends BaseSettingsPage
{
    protected static string $settings = TaxSettings::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedReceiptPercent;

    protected static ?int $navigationSort = 35;

    protected static ?string $slug = 'settings/tax';

    public static function getNavigationLabel(): string
    {
        return __('tax::admin.navigation');
    }

    public function getTitle(): string
    {
        return __('tax::admin.navigation');
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key) => __('tax::admin.settings.'.$key);

        return $schema->components([
            Section::make()->schema([
                Toggle::make('vat_payer')->label($t('vat_payer'))->helperText($t('vat_payer_help'))->live(),
                Toggle::make('prices_include_vat')->label($t('prices_include_vat'))->helperText($t('prices_include_vat_help'))
                    ->visible(fn (Get $get) => (bool) $get('vat_payer')),
            ]),
        ]);
    }
}
