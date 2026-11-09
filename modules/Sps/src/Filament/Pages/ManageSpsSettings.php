<?php

namespace Modules\Sps\Filament\Pages;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Modules\Shipping\Filament\Pages\CarrierSettingsPage;
use Modules\Shipping\Filament\Resources\ShippingMethods\ShippingMethodResource;
use Modules\Sps\SpsSettings;

class ManageSpsSettings extends CarrierSettingsPage
{
    protected static string $settings = SpsSettings::class;

    protected static ?int $navigationSort = 47;

    protected static ?string $slug = 'settings/sps';

    protected static function carrierKey(): string
    {
        return 'sps';
    }

    public function form(Schema $schema): Schema
    {
        $t = fn (string $key, array $replace = []) => __('sps::admin.'.$key, $replace);

        return $schema->components([
            Section::make($t('section_keys'))->description($t('section_keys_help'))->columns(2)->schema([
                TextInput::make('username')->label($t('username'))->maxLength(100),
                TextInput::make('password')->label($t('password'))->password()->revealable()->autocomplete('off')->maxLength(200),
            ]),
            Section::make($t('section_shipments'))->columns(2)->schema([
                TextInput::make('service')->label($t('service'))->helperText($t('service_help'))->required()->maxLength(40),
                Select::make('notify')->label($t('notify'))->selectablePlaceholder(false)
                    ->options(collect([0, 1, 2, 3])->mapWithKeys(fn ($code) => [$code => $t('notify_types.'.$code)])->all()),
                Toggle::make('insure')->label($t('insure'))->helperText($t('insure_help')),
                TextInput::make('cod_iban')->label($t('cod_iban'))->helperText($t('cod_iban_help'))->maxLength(34),
            ]),
        ]);
    }

    /** V menu pod položkou „ShippingMethodResource“ (zobrazí sa po jej otvorení). */
    public static function getNavigationParentItem(): ?string
    {
        return ShippingMethodResource::getNavigationLabel();
    }
}
