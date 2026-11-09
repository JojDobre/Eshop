<?php

namespace Modules\SlovenskaPosta\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Shipping\Filament\Pages\CarrierSettingsPage;
use Modules\SlovenskaPosta\SlovenskaPostaSettings;

class ManageSlovenskaPostaSettings extends CarrierSettingsPage
{
    protected static string $settings = SlovenskaPostaSettings::class;

    protected static ?int $navigationSort = 45;

    protected static ?string $slug = 'settings/slovenskaposta';

    protected static function carrierKey(): string
    {
        return 'slovenskaposta';
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('slovenskaposta::admin.'.$key, $replace);

        return $schema->components([
            Section::make($t('section_keys'))->description($t('section_keys_help'))->columns(2)->schema([
                TextInput::make('user_id')->label($t('user_id'))->maxLength(100),
                TextInput::make('api_key')->label($t('api_key'))->password()->revealable()->autocomplete('off')->maxLength(200),
            ]),
            Section::make($t('section_shipments'))->columns(2)->schema([
                Select::make('parcel_category')->label($t('parcel_category'))->selectablePlaceholder(false)
                    ->options(['b' => $t('categories.b'), 'ek' => $t('categories.ek'), 'zb' => $t('categories.zb')]),
                Select::make('payment_type')->label($t('payment_type'))->selectablePlaceholder(false)
                    ->options(collect(['h', 'up', 'fa', 'pr', 'vsz', 'ol'])->mapWithKeys(fn ($code) => [$code => $t('payments.'.$code)])->all()),
                TextInput::make('iban')->label($t('iban'))->helperText($t('iban_help'))->maxLength(34),
                Toggle::make('contract')->label($t('contract')),
                Toggle::make('insure')->label($t('insure'))->helperText($t('insure_help')),
                Toggle::make('auto_register')->label($t('auto_register'))->helperText($t('auto_register_help')),
            ]),
        ]);
    }

    /** V menu pod položkou „ShippingMethodResource“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return \Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource::getNavigationLabel();
    }
}
