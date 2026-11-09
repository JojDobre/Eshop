<?php

namespace Modules\Catalog\Filament\Pages;

use App\Filament\Pages\Settings\BaseSettingsPage;
use App\Filament\Pages\Settings\ManageFeatures;
use App\Settings\CatalogSettings;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Modules\Catalog\CatalogFeatures;

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
        $d = fn (string $key) => __('catalog::admin.display.'.$key);

        return $schema->components([
            Section::make($d('section'))->columns(2)->schema([
                Toggle::make('show_vat_note')->label($d('show_vat_note')),
                Toggle::make('show_sku')->label($d('show_sku')),
                Toggle::make('show_stock_status')->label($d('show_stock_status'))->live(),
                Toggle::make('show_stock_quantity')->label($d('show_stock_quantity'))->helperText($d('show_stock_quantity_help'))
                    ->visible(fn ($get) => (bool) $get('show_stock_status')),
                Select::make('description_position')->label($d('description_position'))->selectablePlaceholder(false)
                    ->options(['below' => $d('description_below'), 'side' => $d('description_side')]),
                TextInput::make('new_label_days')->label($d('new_label_days'))->helperText($d('new_label_days_help'))
                    ->integer()->minValue(0)->maxValue(365)->required(),
            ]),
            Section::make()->schema([
                TextInput::make('low_stock_threshold')
                    ->label(__('catalog::admin.settings.low_stock_threshold'))
                    ->helperText(__('catalog::admin.settings.low_stock_threshold_help'))
                    ->integer()->minValue(0)->required()->suffix(__('catalog::admin.pieces')),
            ]),
            Section::make(__('catalog::admin.settings.expiration'))
                ->description(__('catalog::admin.settings.expiration_help'))
                ->visible(fn () => CatalogFeatures::enabled('expiration'))
                ->columns(2)
                ->schema([
                    TextInput::make('expiration_warning_days')->label(__('catalog::admin.settings.expiration_warning_days'))
                        ->helperText(__('catalog::admin.settings.expiration_warning_days_help'))
                        ->integer()->minValue(0)->maxValue(3650)->required()->suffix(__('catalog::admin.settings.days')),
                    TextInput::make('expiration_discount_percent')->label(__('catalog::admin.settings.expiration_discount_percent'))
                        ->helperText(__('catalog::admin.settings.expiration_discount_percent_help'))
                        ->integer()->minValue(0)->maxValue(90)->required()->suffix('%'),
                    TextInput::make('expiration_hide_days')->label(__('catalog::admin.settings.expiration_hide_days'))
                        ->helperText(__('catalog::admin.settings.expiration_hide_days_help'))
                        ->integer()->minValue(0)->maxValue(3650)->required()->suffix(__('catalog::admin.settings.days')),
                    Toggle::make('expiration_show_date')->label(__('catalog::admin.settings.expiration_show_date'))
                        ->helperText(__('catalog::admin.settings.expiration_show_date_help')),
                ]),
            Section::make(__('catalog::admin.settings.digital'))
                ->visible(fn () => CatalogFeatures::enabled('digital'))
                ->columns(2)
                ->schema([
                    TextInput::make('digital_download_limit')->label(__('catalog::admin.settings.digital_download_limit'))
                        ->helperText(__('catalog::admin.settings.digital_download_limit_help'))->integer()->minValue(0)->maxValue(1000)->required(),
                    TextInput::make('digital_download_days')->label(__('catalog::admin.settings.digital_download_days'))
                        ->helperText(__('catalog::admin.settings.digital_download_days_help'))->integer()->minValue(0)->maxValue(3650)->required()
                        ->suffix(__('catalog::admin.settings.days')),
                    Toggle::make('digital_deliver_unpaid')->label(__('catalog::admin.settings.digital_deliver_unpaid'))
                        ->helperText(__('catalog::admin.settings.digital_deliver_unpaid_help')),
                ]),
        ]);
    }

    /** V menu pod položkou „ManageFeatures“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ManageFeatures::getNavigationLabel();
    }
}
